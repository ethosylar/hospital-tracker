<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\DashboardOverviewResource;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function overview()
    {
        $today = now()->toDateString();
        $due7  = now()->addDays(7)->toDateString();
        $due14 = now()->addDays(14)->toDateString();

        /**
         * 1) Key counts (same as your current controller)
         */
        $countsRow = Project::query()
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
        $delayedProjects = Project::query()
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
            ->with(['project:id,code,name'])
            ->whereBetween('milestone_date', [$today, $due14])
            ->orderBy('milestone_date')
            ->limit(10)
            ->get(['id','project_id','name','milestone_date']);

        /**
         * 4) Charts you already had
         */
        $projectsByStatus = Project::query()
            ->join('st_project_statuses as s', 's.id', '=', 'dt_projects.project_status_id')
            ->groupBy('s.code', 's.name')
            ->orderBy('s.code')
            ->get([
                DB::raw('s.code as `key`'),
                DB::raw('s.name as label'),
                DB::raw('COUNT(*) as value'),
            ]);

        $projectsByDepartment = Project::query()
            ->join('lt_departments as d', 'd.id', '=', 'dt_projects.department_id')
            ->groupBy('d.code', 'd.name')
            ->orderBy('d.name')
            ->get([
                DB::raw('d.code as `key`'),
                DB::raw('d.name as label'),
                DB::raw('COUNT(*) as value'),
            ]);

        $projectsByPriority = Project::query()
            ->join('lt_priorities as pr', 'pr.id', '=', 'dt_projects.priority_id')
            ->groupBy('pr.code', 'pr.name', 'pr.sort_order')
            ->orderBy('pr.sort_order')
            ->get([
                DB::raw('pr.code as `key`'),
                DB::raw('pr.name as label'),
                DB::raw('COUNT(*) as value'),
            ]);

        $b = Project::query()
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

        $tasksByStatus = ProjectTask::query()
            ->join('st_task_statuses as s', 's.id', '=', 'dt_project_tasks.task_status_id')
            ->groupBy('s.code', 's.name', 's.sort_order')
            ->orderBy('s.sort_order')
            ->get([
                DB::raw('s.code as `key`'),
                DB::raw('s.name as label'),
                DB::raw('COUNT(*) as value'),
            ]);

        $th = Project::query()
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
        $finance = null;

        try {
            $tot = Project::query()
                ->selectRaw("
                    SUM(COALESCE(budget_planned,0)) as budget_planned_total,
                    SUM(COALESCE(budget_actual,0)) as budget_actual_total,
                    SUM(COALESCE(cost_planned,0)) as cost_planned_total,
                    SUM(COALESCE(cost_actual,0)) as cost_actual_total,
                    SUM(COALESCE(funding_planned,0)) as funding_planned_total,
                    SUM(COALESCE(funding_actual,0)) as funding_actual_total
                ")
                ->first();

            $budgetPlannedTotal = (float)($tot->budget_planned_total ?? 0);
            $costActualTotal    = (float)($tot->cost_actual_total ?? 0);

            $utilizationPct = $budgetPlannedTotal > 0
                ? round(($costActualTotal / $budgetPlannedTotal) * 100, 1)
                : null;

            $financeOverview = [
                'budget_planned_total' => $budgetPlannedTotal,
                'budget_actual_total' => (float)($tot->budget_actual_total ?? 0),
                'cost_planned_total' => (float)($tot->cost_planned_total ?? 0),
                'cost_actual_total' => $costActualTotal,
                'funding_planned_total' => (float)($tot->funding_planned_total ?? 0),
                'funding_actual_total' => (float)($tot->funding_actual_total ?? 0),
                'budget_remaining_total' => $budgetPlannedTotal - $costActualTotal,
                'utilization_pct_total' => $utilizationPct,
            ];

            // Budget utilization distribution (by project)
            $ub = Project::query()
                ->selectRaw("
                    SUM(CASE WHEN budget_planned > 0 AND (cost_actual / budget_planned) * 100 BETWEEN 0 AND 20 THEN 1 ELSE 0 END) as b0_20,
                    SUM(CASE WHEN budget_planned > 0 AND (cost_actual / budget_planned) * 100 BETWEEN 20.00001 AND 40 THEN 1 ELSE 0 END) as b20_40,
                    SUM(CASE WHEN budget_planned > 0 AND (cost_actual / budget_planned) * 100 BETWEEN 40.00001 AND 60 THEN 1 ELSE 0 END) as b40_60,
                    SUM(CASE WHEN budget_planned > 0 AND (cost_actual / budget_planned) * 100 BETWEEN 60.00001 AND 80 THEN 1 ELSE 0 END) as b60_80,
                    SUM(CASE WHEN budget_planned > 0 AND (cost_actual / budget_planned) * 100 >= 80.00001 THEN 1 ELSE 0 END) as b80_100,
                    SUM(CASE WHEN budget_planned <= 0 THEN 1 ELSE 0 END) as no_budget
                ")
                ->first();

            $budgetUtilizationDistribution = [
                ['key' => '0_20',   'label' => '0–20%',   'value' => (int)($ub->b0_20 ?? 0)],
                ['key' => '20_40',  'label' => '20–40%',  'value' => (int)($ub->b20_40 ?? 0)],
                ['key' => '40_60',  'label' => '40–60%',  'value' => (int)($ub->b40_60 ?? 0)],
                ['key' => '60_80',  'label' => '60–80%',  'value' => (int)($ub->b60_80 ?? 0)],
                ['key' => '80_100', 'label' => '80%+',    'value' => (int)($ub->b80_100 ?? 0)],
                ['key' => 'none',   'label' => 'No Budget', 'value' => (int)($ub->no_budget ?? 0)],
            ];

            // Planned vs Actual by Department
            $financeByDepartment = Project::query()
                ->join('lt_departments as d', 'd.id', '=', 'dt_projects.department_id')
                ->groupBy('d.code', 'd.name')
                ->orderBy('d.name')
                ->get([
                    DB::raw('d.code as `key`'),
                    DB::raw('d.name as label'),
                    DB::raw('SUM(COALESCE(dt_projects.budget_planned,0)) as budget_planned'),
                    DB::raw('SUM(COALESCE(dt_projects.cost_actual,0)) as cost_actual'),
                ])
                ->map(function ($row) {
                    $planned = (float)($row->budget_planned ?? 0);
                    $actual  = (float)($row->cost_actual ?? 0);
                    $row->utilization_pct = $planned > 0 ? round(($actual / $planned) * 100, 1) : null;
                    return $row;
                });

            // Planned vs Actual by Status
            $financeByStatus = Project::query()
                ->join('st_project_statuses as s', 's.id', '=', 'dt_projects.project_status_id')
                ->groupBy('s.code', 's.name')
                ->orderBy('s.code')
                ->get([
                    DB::raw('s.code as `key`'),
                    DB::raw('s.name as label'),
                    DB::raw('SUM(COALESCE(dt_projects.budget_planned,0)) as budget_planned'),
                    DB::raw('SUM(COALESCE(dt_projects.cost_actual,0)) as cost_actual'),
                ])
                ->map(function ($row) {
                    $planned = (float)($row->budget_planned ?? 0);
                    $actual  = (float)($row->cost_actual ?? 0);
                    $row->utilization_pct = $planned > 0 ? round(($actual / $planned) * 100, 1) : null;
                    return $row;
                });

            // Top utilization projects
            $topBudgetUtilization = Project::query()
                ->where('budget_planned', '>', 0)
                ->select([
                    'id','code','name',
                    'currency',
                    'budget_planned',
                    'cost_actual',
                    DB::raw('ROUND((cost_actual / budget_planned) * 100, 1) as utilization_pct'),
                    DB::raw('ROUND((budget_planned - cost_actual), 2) as remaining_budget'),
                ])
                ->orderByDesc('utilization_pct')
                ->limit(10)
                ->get();

            // Over-budget projects (actual > planned)
            $overBudgetProjects = Project::query()
                ->whereColumn('cost_actual', '>', 'budget_planned')
                ->select([
                    'id','code','name',
                    'currency',
                    'budget_planned',
                    'cost_actual',
                    DB::raw('ROUND((cost_actual - budget_planned), 2) as over_by'),
                ])
                ->orderByDesc('over_by')
                ->limit(10)
                ->get();

            $finance = [
                'overview' => $financeOverview,
                'charts' => [
                    'budget_utilization_distribution' => $budgetUtilizationDistribution,
                    'finance_by_department' => $financeByDepartment,
                    'finance_by_status' => $financeByStatus,
                    'top_budget_utilization' => $topBudgetUtilization,
                    'over_budget_projects' => $overBudgetProjects,
                ],
            ];
        } catch (\Throwable $e) {
            // Don’t kill dashboard if finance fields are not migrated yet.
            $finance = [
                'overview' => null,
                'charts' => [],
                'error' => config('app.debug') ? $e->getMessage() : 'Finance fields not available',
            ];
        }

        return new DashboardOverviewResource([
            'counts' => $counts,
            'delayed_projects' => $delayedProjects,
            'upcoming_milestones' => $upcomingMilestones,
            'charts' => [
                'projects_by_status' => $projectsByStatus,
                'projects_by_department' => $projectsByDepartment,
                'projects_by_priority' => $projectsByPriority,
                'progress_distribution' => $progressDistribution,
                'tasks_by_status' => $tasksByStatus,
                'project_timeline_health' => $projectTimelineHealth,
            ],
            'finance' => $finance,
        ]);
    }
}