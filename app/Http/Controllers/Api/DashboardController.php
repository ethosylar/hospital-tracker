<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\DashboardOverviewResource;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\ProjectAccess;
use Illuminate\Database\Eloquent\Builder;
//use Carbon\Carbon;

class DashboardController extends Controller
{
	public function overview(Request $request)
	{
		$projectBase = ProjectAccess::visibleQuery($request->user());
		$today = now()->toDateString();
		$due7  = now()->addDays(7)->toDateString();
		$due14 = now()->addDays(14)->toDateString();

		if ($request->filled('site_id')) {
			$projectBase->where(
				'dt_projects.site_id',
				(int) $request->site_id
			);
		}

		$visibleProjectIds = (clone $projectBase)
			->select('dt_projects.id');

		/**
		 * 1) Key counts (same as your current controller)
		 */
		$countsRow = (clone $projectBase)
			->join('st_project_statuses as s', 's.id', '=', 'dt_projects.project_status_id')
			->selectRaw("
			COUNT(*) as total,
			SUM(CASE WHEN s.code = 'IN_PROGRESS' THEN 1 ELSE 0 END) as in_progress,
			SUM(CASE WHEN s.code = 'AT_RISK' THEN 1 ELSE 0 END) as at_risk,
			SUM(CASE WHEN s.code = 'DELAYED' THEN 1 ELSE 0 END) as delayed_count,
			SUM(CASE WHEN s.code = 'ON_HOLD' THEN 1 ELSE 0 END) as on_hold,
			SUM(CASE WHEN s.code = 'COMPLETED' THEN 1 ELSE 0 END) as completed,
			
			SUM(
			CASE
			WHEN dt_projects.target_end_date IS NOT NULL
			AND dt_projects.target_end_date BETWEEN ? AND ?
			AND s.code NOT IN ('COMPLETED','CANCELLED')
			THEN 1 ELSE 0
			END
			) as due_in_7_days,
			
			SUM(
			CASE
			WHEN dt_projects.target_end_date IS NOT NULL
			AND dt_projects.target_end_date < ?
			AND s.code NOT IN ('COMPLETED','CANCELLED')
			THEN 1 ELSE 0
			END
			) as overdue_projects,
			
			ROUND(
			AVG(CASE WHEN s.code = 'IN_PROGRESS' THEN dt_projects.progress ELSE NULL END),
			1
			) as avg_progress_in_progress
            ", [$today, $due7, $today])
			->first();

		$counts = [
			'total' => (int)($countsRow->total ?? 0),
			'in_progress' => (int)($countsRow->in_progress ?? 0),
			'at_risk' => (int)($countsRow->at_risk ?? 0),
			'delayed_count' => (int)($countsRow->delayed_count ?? 0),
			'on_hold' => (int)($countsRow->on_hold ?? 0),
			'completed' => (int)($countsRow->completed ?? 0),
			'due_in_7_days' => (int)($countsRow->due_in_7_days ?? 0),
			'overdue_projects' => (int)($countsRow->overdue_projects ?? 0),
			'avg_progress_in_progress' => $countsRow->avg_progress_in_progress !== null
				? (float)$countsRow->avg_progress_in_progress
				: null,
		];

		/**
		 * 2) Delayed projects list (top 10)
		 */
		$delayedProjects = (clone $projectBase)
			->join('st_project_statuses as s', 's.id', '=', 'dt_projects.project_status_id')
			->whereNotNull('dt_projects.target_end_date')
			->whereDate('dt_projects.target_end_date', '<', $today)
			->whereNotIn('s.code', ['COMPLETED', 'CANCELLED'])
			->orderBy('dt_projects.target_end_date')
			->limit(10)
			->get([
				'dt_projects.id',
				'dt_projects.code',
				'dt_projects.name',
				'dt_projects.target_end_date',
				'dt_projects.progress',
				's.code as status_code',
			]);

		/**
		 * 3) Upcoming milestones (next 14 days)
		 */
		$upcomingMilestones = ProjectMilestone::query()
			->whereHas(
				'project',
				fn(Builder $query) =>
				$this->applyProjectRelationScope(
					$query,
					$request
				)
			)
			->with(['project:id,code,name'])
			->whereBetween('milestone_date', [$today, $due14])
			->orderBy('milestone_date')
			->limit(10)
			->get(['id', 'project_id', 'name', 'milestone_date']);

		/**
		 * 4) Charts you already had
		 */
		$projectsByStatus = (clone $projectBase)
			->join('st_project_statuses as s', 's.id', '=', 'dt_projects.project_status_id')
			->groupBy('s.code', 's.name')
			->orderBy('s.code')
			->get([
				DB::raw('s.code as `key`'),
				DB::raw('s.name as label'),
				DB::raw('COUNT(*) as value'),
			]);

		$projectsByDepartment = (clone $projectBase)
			->join('lt_departments as d', 'd.id', '=', 'dt_projects.department_id')
			->groupBy('d.code', 'd.name')
			->orderBy('d.name')
			->get([
				DB::raw('d.code as `key`'),
				DB::raw('d.name as label'),
				DB::raw('COUNT(*) as value'),
			]);

		$projectsByPriority = (clone $projectBase)
			->join('lt_priorities as pr', 'pr.id', '=', 'dt_projects.priority_id')
			->groupBy('pr.code', 'pr.name', 'pr.sort_order')
			->orderBy('pr.sort_order')
			->get([
				DB::raw('pr.code as `key`'),
				DB::raw('pr.name as label'),
				DB::raw('COUNT(*) as value'),
			]);

		$b = (clone $projectBase)
			->selectRaw("
			SUM(CASE WHEN progress BETWEEN 0 AND 20 THEN 1 ELSE 0 END) as b0_20,
			SUM(CASE WHEN progress BETWEEN 21 AND 40 THEN 1 ELSE 0 END) as b21_40,
			SUM(CASE WHEN progress BETWEEN 41 AND 60 THEN 1 ELSE 0 END) as b41_60,
			SUM(CASE WHEN progress BETWEEN 61 AND 80 THEN 1 ELSE 0 END) as b61_80,
			SUM(CASE WHEN progress BETWEEN 81 AND 100 THEN 1 ELSE 0 END) as b81_100
            ")
			->first();

		$progressDistribution = [
			['key' => '0_20',   'label' => '0–20%',   'value' => (int)($b->b0_20 ?? 0)],
			['key' => '21_40',  'label' => '21–40%',  'value' => (int)($b->b21_40 ?? 0)],
			['key' => '41_60',  'label' => '41–60%',  'value' => (int)($b->b41_60 ?? 0)],
			['key' => '61_80',  'label' => '61–80%',  'value' => (int)($b->b61_80 ?? 0)],
			['key' => '81_100', 'label' => '81–100%', 'value' => (int)($b->b81_100 ?? 0)],
		];

		/* $tasksByStatus = ProjectTask::query()
			->join('st_task_statuses as s', 's.id', '=', 'dt_project_tasks.task_status_id')
			->groupBy('s.code', 's.name', 's.sort_order')
			->orderBy('s.sort_order')
			->get([
				DB::raw('s.code as `key`'),
				DB::raw('s.name as label'),
				DB::raw('COUNT(*) as value'),
			]); */

		$tasksByStatus = ProjectTask::query()
			->whereHas(
				'project',
				fn(Builder $query) =>
				$this->applyProjectRelationScope(
					$query,
					$request
				)
			)
			->join(
				'st_task_statuses as s',
				's.id',
				'=',
				'dt_project_tasks.task_status_id'
			)
			->groupBy(
				's.code',
				's.name',
				's.sort_order'
			)
			->orderBy('s.sort_order')
			->get([
				DB::raw('s.code as `key`'),
				DB::raw('s.name as label'),
				DB::raw('COUNT(*) as value'),
			]);

		$th = (clone $projectBase)
			->join('st_project_statuses as s', 's.id', '=', 'dt_projects.project_status_id')
			->selectRaw("
			SUM(
			CASE
			WHEN dt_projects.target_end_date IS NOT NULL
			AND dt_projects.target_end_date < ?
			AND s.code NOT IN ('COMPLETED','CANCELLED')
			THEN 1 ELSE 0
			END
			) as overdue,
			
			SUM(
			CASE
			WHEN dt_projects.target_end_date IS NOT NULL
			AND dt_projects.target_end_date BETWEEN ? AND ?
			AND s.code NOT IN ('COMPLETED','CANCELLED')
			THEN 1 ELSE 0
			END
			) as due_soon,
			
			SUM(
			CASE
			WHEN (dt_projects.target_end_date IS NULL OR dt_projects.target_end_date > ?)
			AND s.code NOT IN ('COMPLETED','CANCELLED')
			THEN 1 ELSE 0
			END
			) as on_track
            ", [$today, $today, $due7, $due7])
			->first();

		$projectTimelineHealth = [
			['key' => 'overdue', 'label' => 'Overdue', 'value' => (int)($th->overdue ?? 0)],
			['key' => 'due_soon', 'label' => 'Due Soon (7d)', 'value' => (int)($th->due_soon ?? 0)],
			['key' => 'on_track', 'label' => 'On Track', 'value' => (int)($th->on_track ?? 0)],
		];

		/**
		 * 5) NEW: Finance/Budget overview + charts
		 * Columns expected on dt_projects:
		 * currency, budget_planned, budget_actual, cost_planned, cost_actual, funding_planned, funding_actual
		 */


		$taskCountsRow = ProjectTask::query()
			->whereHas(
				'project',
				fn(Builder $query) =>
				$this->applyProjectRelationScope(
					$query,
					$request
				)
			)
			->leftJoin(
				'st_task_statuses as ts',
				'ts.id',
				'=',
				'dt_project_tasks.task_status_id'
			)
			->selectRaw("
			COUNT(*) as total,
			
			SUM(
            CASE WHEN ts.code = 'IN_PROGRESS'
            THEN 1 ELSE 0 END
			) as in_progress,
			
			SUM(
            CASE WHEN ts.code IN ('DONE', 'COMPLETED')
            THEN 1 ELSE 0 END
			) as completed,
			
			SUM(
            CASE WHEN ts.code = 'DELAYED'
            THEN 1 ELSE 0 END
			) as delayed_count,
			
			SUM(
            CASE
			WHEN dt_project_tasks.end_date IS NOT NULL
			AND dt_project_tasks.end_date < ?
			AND COALESCE(ts.code, '') NOT IN
			('DONE', 'COMPLETED', 'CANCELLED')
			THEN 1 ELSE 0
            END
			) as overdue,
			
			SUM(
            CASE
			WHEN dt_project_tasks.end_date IS NOT NULL
			AND dt_project_tasks.end_date BETWEEN ? AND ?
			AND COALESCE(ts.code, '') NOT IN
			('DONE', 'COMPLETED', 'CANCELLED')
			THEN 1 ELSE 0
            END
			) as due_in_7_days,
			
			ROUND(AVG(COALESCE(dt_project_tasks.progress, 0)), 1)
            as avg_progress
			", [$today, $today, $due7])
			->first();

		$taskCounts = [
			'total' => (int) ($taskCountsRow->total ?? 0),
			'in_progress' => (int) ($taskCountsRow->in_progress ?? 0),
			'completed' => (int) ($taskCountsRow->completed ?? 0),
			'delayed' => (int) ($taskCountsRow->delayed_count ?? 0),
			'overdue' => (int) ($taskCountsRow->overdue ?? 0),
			'due_in_7_days' => (int) ($taskCountsRow->due_in_7_days ?? 0),
			'avg_progress' => $taskCountsRow->avg_progress !== null
				? (float) $taskCountsRow->avg_progress
				: null,
		];

		$milestoneCountsRow = ProjectMilestone::query()
			->whereHas(
				'project',
				fn(Builder $query) =>
				$this->applyProjectRelationScope(
					$query,
					$request
				)
			)
			->selectRaw("
			COUNT(*) as total,
			
			SUM(
            CASE WHEN UPPER(status) IN ('DONE', 'COMPLETED')
            THEN 1 ELSE 0 END
			) as completed,
			
			SUM(
            CASE WHEN UPPER(status) NOT IN
			('DONE', 'COMPLETED', 'CANCELLED')
            THEN 1 ELSE 0 END
			) as pending,
			
			SUM(
            CASE
			WHEN milestone_date < ?
			AND UPPER(status) NOT IN
			('DONE', 'COMPLETED', 'CANCELLED')
			THEN 1 ELSE 0
            END
			) as overdue,
			
			SUM(
            CASE
			WHEN milestone_date BETWEEN ? AND ?
			AND UPPER(status) NOT IN
			('DONE', 'COMPLETED', 'CANCELLED')
			THEN 1 ELSE 0
            END
			) as due_in_14_days
			", [$today, $today, $due14])
			->first();

		$milestoneCounts = [
			'total' => (int) ($milestoneCountsRow->total ?? 0),
			'completed' => (int) ($milestoneCountsRow->completed ?? 0),
			'pending' => (int) ($milestoneCountsRow->pending ?? 0),
			'overdue' => (int) ($milestoneCountsRow->overdue ?? 0),
			'due_in_14_days' => (int) ($milestoneCountsRow->due_in_14_days ?? 0),
		];

		$overdueTasks = ProjectTask::query()
			->join(
				'dt_projects as p',
				'p.id',
				'=',
				'dt_project_tasks.project_id'
			)
			->whereIn(
				'p.id',
				(clone $visibleProjectIds)
			)
			->leftJoin(
				'st_task_statuses as ts',
				'ts.id',
				'=',
				'dt_project_tasks.task_status_id'
			)
			->whereNotNull('dt_project_tasks.end_date')
			->whereDate('dt_project_tasks.end_date', '<', $today)
			->whereNotIn('ts.code', ['DONE', 'COMPLETED', 'CANCELLED'])
			->orderBy('dt_project_tasks.end_date')
			->limit(10)
			->get([
				'dt_project_tasks.id',
				'dt_project_tasks.project_id',
				'p.code as project_code',
				'p.name as project_name',
				'dt_project_tasks.name',
				'dt_project_tasks.end_date',
				'dt_project_tasks.progress',
				'ts.code as status_code',
			]);

		$milestonesByStatus = ProjectMilestone::query()
			->whereHas(
				'project',
				fn(Builder $query) =>
				$this->applyProjectRelationScope(
					$query,
					$request
				)
			)
			->selectRaw("
			UPPER(COALESCE(status, 'UNKNOWN')) as `key`,
			UPPER(COALESCE(status, 'Unknown')) as label,
			COUNT(*) as value
			")
			->groupBy('status')
			->orderBy('status')
			->get();

		$projectFinanceRows = DB::table('dt_projects as p')
			->whereIn(
				'p.id',
				(clone $visibleProjectIds)
			)
			->leftJoin(
				'dt_project_budget_allocations as a',
				function ($join) {
					$join->on('a.project_id', '=', 'p.id')
						->where('a.is_active', '=', 1);
				}
			)
			->leftJoin(
				'dt_project_budget_lines as l',
				function ($join) {
					$join->on('l.id', '=', 'a.budget_line_id')
						->where('l.is_active', '=', 1);
				}
			)
			->select([
				'p.id',
				'p.code',
				'p.name',
				DB::raw("COALESCE(p.currency_code, 'MYR') as currency_code"),

				DB::raw("
            SUM(
			CASE WHEN l.line_type = 'COST'
			THEN COALESCE(a.planned_amount, 0)
			ELSE 0 END
            ) as planned_cost
			"),

				DB::raw("
            SUM(
			CASE WHEN l.line_type = 'COST'
			THEN COALESCE(a.actual_amount, 0)
			ELSE 0 END
            ) as actual_cost
			"),

				DB::raw("
            SUM(
			CASE WHEN l.line_type = 'COST'
			THEN COALESCE(a.committed_amount, 0)
			ELSE 0 END
            ) as committed_cost
			"),

				DB::raw("
            SUM(
			CASE WHEN l.line_type = 'FUND'
			THEN COALESCE(a.planned_amount, 0)
			ELSE 0 END
            ) as planned_funding
			"),

				DB::raw("
            SUM(
			CASE WHEN l.line_type = 'FUND'
			THEN COALESCE(a.actual_amount, 0)
			ELSE 0 END
            ) as actual_funding
			"),

				DB::raw("
            SUM(
			CASE WHEN l.line_type = 'FUND'
			THEN COALESCE(a.committed_amount, 0)
			ELSE 0 END
            ) as committed_funding
			"),
			])
			->groupBy(
				'p.id',
				'p.code',
				'p.name',
				'p.currency_code'
			)
			->get()
			->map(function ($row) {
				$row->planned_cost = (float) $row->planned_cost;
				$row->actual_cost = (float) $row->actual_cost;
				$row->committed_cost = (float) $row->committed_cost;
				$row->spent_cost =
					$row->actual_cost + $row->committed_cost;

				$row->planned_funding = (float) $row->planned_funding;
				$row->actual_funding = (float) $row->actual_funding;
				$row->committed_funding = (float) $row->committed_funding;
				$row->received_funding =
					$row->actual_funding + $row->committed_funding;

				$row->variance =
					$row->received_funding - $row->spent_cost;

				$row->utilization_pct = $row->planned_cost > 0
					? round(
						($row->spent_cost / $row->planned_cost) * 100,
						1
					)
					: null;

				return $row;
			});

		$plannedCost = (float) $projectFinanceRows->sum('planned_cost');
		$actualCost = (float) $projectFinanceRows->sum('actual_cost');
		$committedCost = (float) $projectFinanceRows->sum('committed_cost');
		$spentCost = $actualCost + $committedCost;

		$plannedFunding =
			(float) $projectFinanceRows->sum('planned_funding');

		$actualFunding =
			(float) $projectFinanceRows->sum('actual_funding');

		$committedFunding =
			(float) $projectFinanceRows->sum('committed_funding');

		$receivedFunding = $actualFunding + $committedFunding;

		$finance = [
			'currency_code' =>
			$projectFinanceRows->first()->currency_code ?? 'MYR',

			'planned_cost' => $plannedCost,
			'actual_cost' => $actualCost,
			'committed_cost' => $committedCost,
			'spent_cost' => $spentCost,

			'planned_funding' => $plannedFunding,
			'actual_funding' => $actualFunding,
			'committed_funding' => $committedFunding,
			'received_funding' => $receivedFunding,

			'variance' => $receivedFunding - $spentCost,

			'cost_utilization_pct' => $plannedCost > 0
				? round(($spentCost / $plannedCost) * 100, 1)
				: null,

			'funding_coverage_pct' => $spentCost > 0
				? round(($receivedFunding / $spentCost) * 100, 1)
				: null,
		];

		$budgetUtilizationDistribution = [
			[
				'key' => '0_50',
				'label' => '0–50%',
				'value' => $projectFinanceRows
					->filter(
						fn($x) =>
						$x->utilization_pct !== null &&
							$x->utilization_pct <= 50
					)
					->count(),
			],
			[
				'key' => '50_80',
				'label' => '51–80%',
				'value' => $projectFinanceRows
					->filter(
						fn($x) =>
						$x->utilization_pct > 50 &&
							$x->utilization_pct <= 80
					)
					->count(),
			],
			[
				'key' => '80_100',
				'label' => '81–100%',
				'value' => $projectFinanceRows
					->filter(
						fn($x) =>
						$x->utilization_pct > 80 &&
							$x->utilization_pct <= 100
					)
					->count(),
			],
			[
				'key' => 'over_100',
				'label' => 'Over 100%',
				'value' => $projectFinanceRows
					->filter(
						fn($x) =>
						$x->utilization_pct > 100
					)
					->count(),
			],
			[
				'key' => 'none',
				'label' => 'No Cost Budget',
				'value' => $projectFinanceRows
					->filter(
						fn($x) =>
						$x->utilization_pct === null
					)
					->count(),
			],
		];

		$overBudgetProjects = $projectFinanceRows
			->filter(
				fn($x) =>
				$x->planned_cost > 0 &&
					$x->spent_cost > $x->planned_cost
			)
			->map(function ($x) {
				return [
					'id' => (int) $x->id,
					'code' => $x->code,
					'name' => $x->name,
					'planned_cost' => $x->planned_cost,
					'spent_cost' => $x->spent_cost,
					'over_by' => $x->spent_cost - $x->planned_cost,
					'utilization_pct' => $x->utilization_pct,
				];
			})
			->sortByDesc('over_by')
			->take(10)
			->values();

		$financeByProject = $projectFinanceRows
			->sortByDesc('planned_cost')
			->take(10)
			->map(fn($x) => [
				'id' => (int) $x->id,
				'key' => $x->code,
				'label' => $x->code,
				'planned_cost' => $x->planned_cost,
				'spent_cost' => $x->spent_cost,
				'received_funding' => $x->received_funding,
				'variance' => $x->variance,
				'utilization_pct' => $x->utilization_pct,
			])
			->values();

		$financeByDepartment = DB::table('dt_projects as p')
			->whereIn(
				'p.id',
				(clone $visibleProjectIds)
			)
			->join('lt_departments as d', 'd.id', '=', 'p.department_id')
			->leftJoin(
				'dt_project_budget_allocations as a',
				function ($join) {
					$join->on('a.project_id', '=', 'p.id')
						->where('a.is_active', '=', 1);
				}
			)
			->leftJoin(
				'dt_project_budget_lines as l',
				function ($join) {
					$join->on('l.id', '=', 'a.budget_line_id')
						->where('l.is_active', '=', 1);
				}
			)
			->select([
				DB::raw('d.code as `key`'),
				DB::raw('d.name as label'),

				DB::raw("
            SUM(
			CASE WHEN l.line_type = 'COST'
			THEN COALESCE(a.planned_amount, 0)
			ELSE 0 END
            ) as planned_cost
			"),

				DB::raw("
            SUM(
			CASE WHEN l.line_type = 'COST'
			THEN COALESCE(a.actual_amount, 0)
			+ COALESCE(a.committed_amount, 0)
			ELSE 0 END
            ) as spent_cost
			"),

				DB::raw("
            SUM(
			CASE WHEN l.line_type = 'FUND'
			THEN COALESCE(a.actual_amount, 0)
			+ COALESCE(a.committed_amount, 0)
			ELSE 0 END
            ) as received_funding
			"),
			])
			->groupBy('d.id', 'd.code', 'd.name')
			->orderBy('d.name')
			->get()
			->map(function ($row) {
				$row->planned_cost = (float) $row->planned_cost;
				$row->spent_cost = (float) $row->spent_cost;
				$row->received_funding =
					(float) $row->received_funding;

				$row->variance =
					$row->received_funding - $row->spent_cost;

				return $row;
			});

		$projectDueMonths = (clone $projectBase)
			->whereNotNull('target_end_date')
			->whereBetween(
				'target_end_date',
				[
					now()->startOfMonth(),
					now()->addMonths(5)->endOfMonth(),
				]
			)
			->selectRaw("
			DATE_FORMAT(target_end_date, '%Y-%m') as month_key,
			COUNT(*) as total
			")
			->groupBy('month_key')
			->pluck('total', 'month_key');

		/* $milestoneDueMonths = ProjectMilestone::query()
			->whereNotNull('milestone_date')
			->whereBetween(
				'milestone_date',
				[
					now()->startOfMonth(),
					now()->addMonths(5)->endOfMonth(),
				]
			)
			->selectRaw("
			DATE_FORMAT(milestone_date, '%Y-%m') as month_key,
			COUNT(*) as total
			")
			->groupBy('month_key')
			->pluck('total', 'month_key'); */

		$milestoneDueMonths =
			ProjectMilestone::query()
			->whereHas(
				'project',
				fn(Builder $query) =>
				$this->applyProjectRelationScope(
					$query,
					$request
				)
			)
			->whereNotNull('milestone_date')
			->whereBetween(
				'milestone_date',
				[
					now()->startOfMonth(),
					now()
						->addMonths(5)
						->endOfMonth(),
				]
			)
			->selectRaw("
            DATE_FORMAT(
                milestone_date,
                '%Y-%m'
            ) as month_key,
            COUNT(*) as total
        ")
			->groupBy('month_key')
			->pluck(
				'total',
				'month_key'
			);

		$deliveryForecast = collect(range(0, 5))
			->map(function ($offset) use (
				$projectDueMonths,
				$milestoneDueMonths
			) {
				$date = now()->startOfMonth()->addMonths($offset);
				$key = $date->format('Y-m');

				return [
					'month' => $key,
					'label' => $date->format('M Y'),
					'projects_due' =>
					(int) ($projectDueMonths[$key] ?? 0),
					'milestones_due' =>
					(int) ($milestoneDueMonths[$key] ?? 0),
				];
			});



		return new DashboardOverviewResource([
			'counts' => $counts,
			'task_counts' => $taskCounts,
			'milestone_counts' => $milestoneCounts,

			'finance' => $finance,

			'delayed_projects' => $delayedProjects,
			'overdue_tasks' => $overdueTasks,
			'upcoming_milestones' => $upcomingMilestones,
			'over_budget_projects' => $overBudgetProjects,

			'charts' => [
				'projects_by_status' => $projectsByStatus,
				'projects_by_department' => $projectsByDepartment,
				'projects_by_priority' => $projectsByPriority,
				'progress_distribution' => $progressDistribution,

				'tasks_by_status' => $tasksByStatus,
				'milestones_by_status' => $milestonesByStatus,
				'project_timeline_health' => $projectTimelineHealth,

				'budget_utilization_distribution' =>
				$budgetUtilizationDistribution,

				'finance_by_department' => $financeByDepartment,
				'finance_by_project' => $financeByProject,
				'delivery_forecast' => $deliveryForecast,
			],
		]);
	}

	private function applyProjectRelationScope(Builder $query, Request $request): void
	{
		ProjectAccess::applyViewScope(
			$query,
			$request->user(),
			'dt_projects.site_id'
		);

		if ($request->filled('site_id')) {
			$query->where(
				'dt_projects.site_id',
				(int) $request->input('site_id')
			);
		}
	}
}
