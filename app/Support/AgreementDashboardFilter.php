<?php
	
	namespace App\Support;
	
	use Carbon\Carbon;
	use Illuminate\Database\Eloquent\Builder;
	
	class AgreementDashboardFilter
	{
		public const CURRENT = 'current';
		public const ONGOING = 'ongoing';
		public const EXPIRING_SOON = 'expiring_soon';
		public const EXPIRED = 'expired';
		public const PENDING_APPROVAL = 'pending_approval';
		public const AUTO_RENEWAL = 'auto_renewal';
		
		public static function allowed(): array
		{
			return [
            self::CURRENT,
            self::ONGOING,
            self::EXPIRING_SOON,
            self::EXPIRED,
            self::PENDING_APPROVAL,
            self::AUTO_RENEWAL,
			];
		}
		
		/**
			* Apply a dashboard card definition to an Agreement query.
			*
			* IMPORTANT:
			* Both AgreementDashboardController and AgreementController use this
			* class so a dashboard card count and the Agreement Management list
			* opened from that card cannot drift apart.
		*/
		public static function apply(
        Builder $query,
        string $filter,
        Carbon $today
		): Builder {
			$filter = strtolower(trim($filter));
			
			switch ($filter) {
				case self::CURRENT:
                return $query->where('is_current_version', true);
				
				case self::ONGOING:
                // Explicit product definition:
                // Ongoing = current Agreement version + ACTIVE status only.
                return $query
				->where('is_current_version', true)
				->whereHas(
				'status',
				fn (Builder $statusQuery) => $statusQuery->where(
				'code',
				'ACTIVE'
				)
				);
				
				case self::EXPIRING_SOON:
                return $query
				->where('is_current_version', true)
				->whereHas(
				'status',
				fn (Builder $statusQuery) => $statusQuery->where(
				'code',
				'EXPIRING_SOON'
				)
				);
				
				case self::EXPIRED:
                return $query
				->where('is_current_version', true)
				->whereHas(
				'status',
				fn (Builder $statusQuery) => $statusQuery->where(
				'code',
				'EXPIRED'
				)
				);
				
				case self::PENDING_APPROVAL:
                return $query
				->where('is_current_version', true)
				->whereHas(
				'status',
				fn (Builder $statusQuery) => $statusQuery->where(
				'code',
				'PENDING_APPROVAL'
				)
				);
				
				case self::AUTO_RENEWAL:
                // Preserve the dashboard's existing intent: auto-renewal
                // agreements that are currently in force and not expired.
                return $query
				->where('is_current_version', true)
				->where('auto_renewal', true)
				->whereHas(
				'status',
				fn (Builder $statusQuery) => $statusQuery->whereIn(
				'code',
				['ACTIVE', 'EXPIRING_SOON']
				)
				)
				->where(function (Builder $dateQuery) use ($today) {
					$dateQuery
					->whereNull('effective_date')
					->orWhereDate(
					'effective_date',
					'<=',
					$today->toDateString()
					);
				})
				->whereNotNull('expiry_date')
				->whereDate(
				'expiry_date',
				'>=',
				$today->toDateString()
				);
				
				default:
                // Request validation should prevent this path.
                return $query->whereRaw('1 = 0');
			}
		}
		
		/**
			* Agreements eligible for the expiry timeline.
			*
			* Dated rows:
			*   current + ACTIVE/EXPIRING_SOON + already effective + future expiry.
			*
			* No-expiry rows (optional):
			*   current + ACTIVE + already effective + expiry_date IS NULL.
		*/
		public static function applyTimelineEligibility(
        Builder $query,
        Carbon $today,
        bool $includeNoExpiry = false
		): Builder {
			$query
            ->where('is_current_version', true)
            ->where(function (Builder $dateQuery) use ($today) {
                $dateQuery
				->whereNull('effective_date')
				->orWhereDate(
				'effective_date',
				'<=',
				$today->toDateString()
				);
			})
            ->where(function (Builder $eligibility) use (
			$today,
			$includeNoExpiry
            ) {
                $eligibility->where(function (Builder $dated) use ($today) {
                    $dated
					->whereNotNull('expiry_date')
					->whereDate(
					'expiry_date',
					'>=',
					$today->toDateString()
					)
					->whereHas(
					'status',
					fn (Builder $statusQuery) => $statusQuery->whereIn(
					'code',
					['ACTIVE', 'EXPIRING_SOON']
					)
					);
				});
				
                if ($includeNoExpiry) {
                    $eligibility->orWhere(function (Builder $noExpiry) {
                        $noExpiry
						->whereNull('expiry_date')
						->whereHas(
						'status',
						fn (Builder $statusQuery) => $statusQuery->where(
						'code',
						'ACTIVE'
						)
						);
					});
				}
			});
			
			return $query;
		}
	}	