<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AgreementDashboardRequest;
use App\Models\Agreement;
use App\Models\AgreementLifecycleEvent;
use App\Support\AgreementDashboardFilter;
use App\Support\ApiErrorCode;
use App\Support\ApiResponse;
use App\Support\AgreementAccess;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Throwable;

class AgreementDashboardController extends Controller
{
	public function overview(AgreementDashboardRequest $request)
	{
		$data = $request->validated();

		$today = today()->startOfDay();
		$timelineMonths = (int) ($data['timeline_months'] ?? 12);
		$timelineAll = (bool) ($data['timeline_all'] ?? false);
		$includeNoExpiry = (bool) ($data['include_no_expiry'] ?? false);
		$timelineLimit = (int) ($data['timeline_limit'] ?? 100);
		$recentLimit = (int) ($data['recent_limit'] ?? 10);

		try {
			/*
					* Portfolio base:
					* visibility and optional dashboard filters are applied here.
					* Card definitions themselves are applied through
					* AgreementDashboardFilter so the dashboard counts and the
					* Agreement Management dashboard_filter list use identical rules.
				*/
			$portfolioBase = Agreement::query();

			$this->applyVisibilityScope($portfolioBase, $request);
			$this->applyFilters($portfolioBase, $data);

			$currentBase = AgreementDashboardFilter::apply(clone $portfolioBase, AgreementDashboardFilter::CURRENT, $today);
			$timelineEligible = AgreementDashboardFilter::applyTimelineEligibility(clone $portfolioBase, $today, $includeNoExpiry);

			$summary = [
				'total_current' => AgreementDashboardFilter::apply(
					clone $portfolioBase,
					AgreementDashboardFilter::CURRENT,
					$today
				)->count(),

				// Explicitly current + ACTIVE only.
				'ongoing' => AgreementDashboardFilter::apply(
					clone $portfolioBase,
					AgreementDashboardFilter::ONGOING,
					$today
				)->count(),

				'expiring_soon' => AgreementDashboardFilter::apply(
					clone $portfolioBase,
					AgreementDashboardFilter::EXPIRING_SOON,
					$today
				)->count(),

				'expired' => AgreementDashboardFilter::apply(
					clone $portfolioBase,
					AgreementDashboardFilter::EXPIRED,
					$today
				)->count(),

				'pending_approval' => AgreementDashboardFilter::apply(
					clone $portfolioBase,
					AgreementDashboardFilter::PENDING_APPROVAL,
					$today
				)->count(),

				'auto_renewal' => AgreementDashboardFilter::apply(
					clone $portfolioBase,
					AgreementDashboardFilter::AUTO_RENEWAL,
					$today
				)->count(),

				'approved_not_started' => (clone $currentBase)
					->whereHas(
						'status',
						fn(Builder $q) => $q->where('code', 'APPROVED')
					)
					->whereNotNull('effective_date')
					->whereDate(
						'effective_date',
						'>',
						$today->toDateString()
					)
					->count(),

				// Cumulative expiry warning windows. These intentionally use
				// live timeline eligibility (ACTIVE + EXPIRING_SOON), not the
				// Ongoing card definition, so agreements already marked
				// EXPIRING_SOON remain represented in expiry warning counts.
				'expiring_within_30_days' => (clone $timelineEligible)
					->whereNotNull('expiry_date')
					->whereDate(
						'expiry_date',
						'<=',
						$today->copy()->addDays(30)->toDateString()
					)
					->count(),

				'expiring_within_60_days' => (clone $timelineEligible)
					->whereNotNull('expiry_date')
					->whereDate(
						'expiry_date',
						'<=',
						$today->copy()->addDays(60)->toDateString()
					)
					->count(),

				'expiring_within_90_days' => (clone $timelineEligible)
					->whereNotNull('expiry_date')
					->whereDate(
						'expiry_date',
						'<=',
						$today->copy()->addDays(90)->toDateString()
					)
					->count(),

				/*
					* Date has passed but lifecycle status was not yet closed.
				*/
				'overdue_unclosed' => (clone $currentBase)
					->whereNotNull('expiry_date')
					->whereDate(
						'expiry_date',
						'<',
						$today->toDateString()
					)
					->whereHas(
						'status',
						fn(Builder $q) => $q->whereIn(
							'code',
							['APPROVED', 'ACTIVE', 'EXPIRING_SOON']
						)
					)
					->count(),

				'terminated' => (clone $currentBase)
					->whereHas(
						'status',
						fn(Builder $q) => $q->where(
							'code',
							'TERMINATED'
						)
					)
					->count(),

				'without_expiry_date' => (clone $currentBase)
					->whereNull('expiry_date')
					->whereHas(
						'status',
						fn(Builder $q) => $q->whereIn(
							'code',
							['APPROVED', 'ACTIVE', 'EXPIRING_SOON']
						)
					)
					->count(),
			];

			/*
					* ONLY_FULL_GROUP_BY-safe aggregation.
					* Database groups by raw currency_code; PHP then normalizes
					* NULL/blank values to MYR and merges them.
				*/
			$contractValueByCurrency = (clone $currentBase)
				->whereNotNull('contract_value')
				->selectRaw(
					'currency_code, SUM(contract_value) as amount'
				)
				->groupBy('currency_code')
				->orderBy('currency_code')
				->get()
				->groupBy(function ($row) {
					$currency = strtoupper(
						trim((string) $row->currency_code)
					);

					return $currency !== ''
						? $currency
						: 'MYR';
				})
				->map(function ($rows, $currencyCode) {
					return [
						'currency_code' => $currencyCode,
						'amount' => number_format(
							$rows->sum(
								fn($row) => (float) $row->amount
							),
							2,
							'.',
							''
						),
					];
				})
				->values();

			$statusDistribution = (clone $currentBase)
				->join(
					'st_agreement_statuses as agreement_status',
					'agreement_status.id',
					'=',
					'dt_agreements.agreement_status_id'
				)
				->selectRaw(
					'agreement_status.id, agreement_status.code, ' .
						'agreement_status.name, COUNT(dt_agreements.id) as total'
				)
				->groupBy(
					'agreement_status.id',
					'agreement_status.code',
					'agreement_status.name',
					'agreement_status.sort_order'
				)
				->orderBy('agreement_status.sort_order')
				->get()
				->map(fn($row) => [
					'id' => (int) $row->id,
					'code' => $row->code,
					'name' => $row->name,
					'count' => (int) $row->total,
				])
				->values();

			$categoryDistribution = (clone $currentBase)
				->join(
					'lt_agreement_categories as agreement_category',
					'agreement_category.id',
					'=',
					'dt_agreements.agreement_category_id'
				)
				->selectRaw(
					'agreement_category.id, agreement_category.code, ' .
						'agreement_category.name, COUNT(dt_agreements.id) as total'
				)
				->groupBy(
					'agreement_category.id',
					'agreement_category.code',
					'agreement_category.name',
					'agreement_category.sort_order'
				)
				->orderBy('agreement_category.sort_order')
				->get()
				->map(fn($row) => [
					'id' => (int) $row->id,
					'code' => $row->code,
					'name' => $row->name,
					'count' => (int) $row->total,
				])
				->values();

			/*
					* Determine timeline end.
					*
					* Normal mode: today + timeline_months.
					* All mode: latest future dated expiry in the user's visible scope.
					* No-expiry rows do not determine a timeline end.
				*/
			$timelineEnd = null;

			if ($timelineAll) {
				$maxExpiry = (clone $timelineEligible)
					->whereNotNull('expiry_date')
					->max('expiry_date');

				if ($maxExpiry) {
					$timelineEnd = Carbon::parse($maxExpiry)
						->startOfDay();
				}
			} else {
				$timelineEnd = $today
					->copy()
					->addMonthsNoOverflow($timelineMonths)
					->endOfDay();
			}

			/*
					* Monthly expiry counts. NULL expiry rows are intentionally not
					* included because they have no month. They still appear in the
					* detailed agreements timeline when include_no_expiry=1.
				*/
			$monthlyQuery = (clone $timelineEligible)
				->whereNotNull('expiry_date');

			if (!$timelineAll && $timelineEnd) {
				$monthlyQuery->whereDate(
					'expiry_date',
					'<=',
					$timelineEnd->toDateString()
				);
			}

			$monthlyRaw = $monthlyQuery
				->selectRaw(
					"DATE_FORMAT(expiry_date, '%Y-%m') as expiry_month, " .
						'COUNT(*) as total'
				)
				->groupByRaw("DATE_FORMAT(expiry_date, '%Y-%m')")
				->pluck('total', 'expiry_month');

			$expiryByMonth = collect();

			if ($timelineEnd) {
				$cursor = $today->copy()->startOfMonth();
				$lastMonth = $timelineEnd->copy()->startOfMonth();

				while ($cursor->lte($lastMonth)) {
					$key = $cursor->format('Y-m');

					$expiryByMonth->push([
						'month' => $key,
						'label' => $cursor->format('M Y'),
						'count' => (int) ($monthlyRaw[$key] ?? 0),
					]);

					$cursor->addMonthNoOverflow();
				}
			}

			/*
					* Detailed timeline rows.
					*
					* timeline_all=0 -> future expiries up to timelineEnd plus optional
					*                   active/current no-expiry rows.
					* timeline_all=1 -> all future expiries regardless of distance plus
					*                   optional active/current no-expiry rows.
				*/
			$timelineRowsQuery = clone $timelineEligible;

			if (!$timelineAll && $timelineEnd) {
				$timelineRowsQuery->where(function (Builder $range) use (
					$timelineEnd,
					$includeNoExpiry
				) {
					$range->whereDate(
						'expiry_date',
						'<=',
						$timelineEnd->toDateString()
					);

					if ($includeNoExpiry) {
						$range->orWhereNull('expiry_date');
					}
				});
			}

			$timelineRows = $timelineRowsQuery
				->with([
					'status:id,code,name',
					'site:id,code,name,short_name',
					'department:id,site_id,code,name',
					'owner:id,name,email',
					'counterparty:id,code,legal_name,trading_name',
					'category:id,code,name',
					'type:id,agreement_category_id,code,name',
				])
				// Dated expiries first, no-expiry rows last.
				->orderByRaw(
					'CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END'
				)
				->orderBy('expiry_date')
				->orderBy('agreement_no')
				->limit($timelineLimit)
				->get()
				->map(function (Agreement $agreement) use ($today) {
					$expiryDate = $agreement->expiry_date
						? $agreement->expiry_date->copy()->startOfDay()
						: null;

					$daysToExpiry = $expiryDate ? (int) $today->diffInDays($expiryDate, false) : null;

					return [
						'id' => (int) $agreement->id,
						'agreement_no' => $agreement->agreement_no,
						'title' => $agreement->title,

						'status' => $agreement->status ? [
							'id' => (int) $agreement->status->id,
							'code' => $agreement->status->code,
							'name' => $agreement->status->name,
						] : null,

						'department' => $agreement->department ? [
							'id' => (int) $agreement->department->id,
							'code' => $agreement->department->code,
							'name' => $agreement->department->name,
						] : null,

						'owner' => $agreement->owner ? [
							'id' => (int) $agreement->owner->id,
							'name' => $agreement->owner->name,
							'email' => $agreement->owner->email,
						] : null,

						'counterparty' => $agreement->counterparty ? [
							'id' => (int) $agreement->counterparty->id,
							'code' => $agreement->counterparty->code,
							'legal_name' => $agreement->counterparty->legal_name,
							'trading_name' => $agreement->counterparty->trading_name,
						] : null,

						'category' => $agreement->category ? [
							'id' => (int) $agreement->category->id,
							'code' => $agreement->category->code,
							'name' => $agreement->category->name,
						] : null,

						'type' => $agreement->type ? [
							'id' => (int) $agreement->type->id,
							'code' => $agreement->type->code,
							'name' => $agreement->type->name,
						] : null,

						'site' => $agreement->site
							? [
								'id' =>
								(int) $agreement->site->id,

								'code' =>
								$agreement->site->code,

								'name' =>
								$agreement->site->name,

								'short_name' =>
								$agreement->site->short_name,
							]
							: null,

						'effective_date' => $agreement->effective_date?->format('Y-m-d'),
						'timeline_start_date' => $today->format('Y-m-d'),
						'expiry_date' => $agreement->expiry_date?->format('Y-m-d'),
						'days_to_expiry' => $daysToExpiry,
						'expiry_band' => $this->expiryBand($daysToExpiry),
						'contract_value' => $agreement->contract_value,
						'currency_code' => $agreement->currency_code,
						'auto_renewal' => (bool) $agreement->auto_renewal,
						'lifecycle_type' => $agreement->lifecycle_type,
						'revision_no' => (int) $agreement->revision_no,
						'renewal_sequence' => (int) $agreement->renewal_sequence,
					];
				})
				->values();

			/*
					* Recent lifecycle history may include historical versions, but
					* visibility and optional dashboard filters remain enforced.
				*/
			$recentActivityQuery = AgreementLifecycleEvent::query()
				->whereHas('agreement', function (Builder $agreementQuery) use (
					$request,
					$data
				) {
					$this->applyVisibilityScope($agreementQuery, $request);
					$this->applyFilters($agreementQuery, $data);
				})
				->with([
					'agreement:id,site_id,agreement_no,title,department_id,owner_user_id',
					'fromStatus:id,code,name',
					'toStatus:id,code,name',
					'relatedAgreement:id,agreement_no,title',
					'performedBy:id,name,email',
				])
				->orderByDesc('event_at')
				->orderByDesc('id')
				->limit($recentLimit);

			$recentActivity = $recentActivityQuery
				->get()
				->map(fn(AgreementLifecycleEvent $event) => [
					'id' => (int) $event->id,
					'agreement' => $event->agreement ? [
						'id' => (int) $event->agreement->id,
						'site_id' => (int) $event->agreement->site_id,
						'agreement_no' => $event->agreement->agreement_no,
						'title' => $event->agreement->title,
					] : null,
					'event_type' => $event->event_type,
					'from_status' => $event->fromStatus ? [
						'id' => (int) $event->fromStatus->id,
						'code' => $event->fromStatus->code,
						'name' => $event->fromStatus->name,
					] : null,
					'to_status' => $event->toStatus ? [
						'id' => (int) $event->toStatus->id,
						'code' => $event->toStatus->code,
						'name' => $event->toStatus->name,
					] : null,
					'related_agreement' => $event->relatedAgreement ? [
						'id' => (int) $event->relatedAgreement->id,
						'agreement_no' =>
						$event->relatedAgreement->agreement_no,
						'title' => $event->relatedAgreement->title,
					] : null,
					'performed_by' => $event->performedBy ? [
						'id' => (int) $event->performedBy->id,
						'name' => $event->performedBy->name,
						'email' => $event->performedBy->email,
					] : null,
					'reason' => $event->reason,
					'event_at' => $event->event_at?->toIso8601String(),
				])
				->values();

			return response()->json([
				'data' => [
					'generated_at' => now()->toIso8601String(),

					'scope' => [
						'as_of_date' => $today->format('Y-m-d'),
						'timeline_start' => $today->format('Y-m-d'),
						'timeline_end' =>
						$timelineEnd?->format('Y-m-d'),
						'timeline_months' => $timelineAll
							? null
							: $timelineMonths,
						'timeline_all' => $timelineAll,
						'include_no_expiry' => $includeNoExpiry,
						'timeline_limit' => $timelineLimit,
					],

					'summary' => $summary,
					'contract_value_by_currency' =>
					$contractValueByCurrency,
					'status_distribution' => $statusDistribution,
					'category_distribution' => $categoryDistribution,

					'expiry_timeline' => [
						'monthly' => $expiryByMonth,
						'agreements' => $timelineRows,
					],

					'recent_lifecycle_activity' => $recentActivity,
				],
			]);
		} catch (Throwable $e) {
			report($e);

			return ApiResponse::error(
				ApiErrorCode::AGREEMENT_DASHBOARD_LOAD_FAILED,
				'Failed to load agreement dashboard.',
				$this->errorDetails($e),
				500
			);
		}
	}

	private function applyVisibilityScope(Builder $query, Request $request): void
	{
		$user = $request->user();

		AgreementAccess::applyViewScope($query, $user, 'dt_agreements.site_id');

		if ($user->hasPermission('agreements.view.all')) {
			return;
		}

		$canViewDepartment = $user->hasPermission('agreements.view.department');
		$canViewOwn = $user->hasPermission('agreements.view.own');

		if (!$canViewDepartment && !$canViewOwn) {
			$query->whereRaw('1 = 0');
			return;
		}

		$query->where(
			function (Builder $scope) use (
				$user,
				$canViewDepartment,
				$canViewOwn
			) {
				if (
					$canViewDepartment
					&& $user->department_id
				) {
					$scope->where(
						'department_id',
						$user->department_id
					);
				}

				if ($canViewOwn) {
					if (
						$canViewDepartment
						&& $user->department_id
					) {
						$scope->orWhere(
							'owner_user_id',
							$user->id
						);
					} else {
						$scope->where(
							'owner_user_id',
							$user->id
						);
					}
				}
			}
		);
	}

	/**
	 * Optional dashboard filters. Visibility is applied before these filters,
	 * so they can narrow but never widen the user's allowed Agreement set.
	 */
	private function applyFilters(Builder $query, array $data): void
	{
		if (!empty($data['site_id'])) {
			$query->where(
				'dt_agreements.site_id',
				(int) $data['site_id']
			);
		}
		foreach (
			[
				'department_id',
				'owner_user_id',
				'counterparty_id',
				'agreement_category_id',
				'agreement_type_id',
			] as $field
		) {
			if (
				array_key_exists($field, $data)
				&& $data[$field] !== null
			) {
				$query->where($field, (int) $data[$field]);
			}
		}
	}

	private function expiryBand(?int $daysToExpiry): string
	{
		if ($daysToExpiry === null) {
			return 'NO_EXPIRY';
		}

		if ($daysToExpiry < 0) {
			return 'OVERDUE';
		}

		if ($daysToExpiry <= 30) {
			return '0_30_DAYS';
		}

		if ($daysToExpiry <= 60) {
			return '31_60_DAYS';
		}

		if ($daysToExpiry <= 90) {
			return '61_90_DAYS';
		}

		if ($daysToExpiry <= 180) {
			return '91_180_DAYS';
		}

		if ($daysToExpiry <= 365) {
			return '181_365_DAYS';
		}

		return 'OVER_365_DAYS';
	}

	private function errorDetails(Throwable $e): array
	{
		if (!config('app.debug')) {
			return [];
		}

		return [
			'exception' => $e->getMessage(),
			'exception_class' => get_class($e),
		];
	}
}
