<?php
	
	namespace App\Http\Resources\Dashboard;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class DashboardOverviewResource extends JsonResource
	{
		public function toArray($request): array
		{
			return [
			'counts' => $this->resource['counts'],
			'task_counts' => $this->resource['task_counts'],
			'milestone_counts' => $this->resource['milestone_counts'],
			
			'finance' => $this->resource['finance'],
			
			'delayed_projects' => ProjectMiniResource::collection(
            $this->resource['delayed_projects']
			),
			
			'overdue_tasks' => $this->resource['overdue_tasks'],
			
			'upcoming_milestones' => MilestoneMiniResource::collection(
            $this->resource['upcoming_milestones']
			),
			
			'over_budget_projects' => $this->resource['over_budget_projects'],
			
			'charts' => [
            'projects_by_status' => ChartItemResource::collection(
			$this->resource['charts']['projects_by_status']
            ),
			
            'projects_by_department' => ChartItemResource::collection(
			$this->resource['charts']['projects_by_department']
            ),
			
            'projects_by_priority' => ChartItemResource::collection(
			$this->resource['charts']['projects_by_priority']
            ),
			
            'progress_distribution' =>
			$this->resource['charts']['progress_distribution'],
			
            'tasks_by_status' => ChartItemResource::collection(
			$this->resource['charts']['tasks_by_status']
            ),
			
            'milestones_by_status' =>
			$this->resource['charts']['milestones_by_status'],
			
            'project_timeline_health' =>
			$this->resource['charts']['project_timeline_health'],
			
            'budget_utilization_distribution' =>
			$this->resource['charts']['budget_utilization_distribution'],
			
            'finance_by_department' =>
			$this->resource['charts']['finance_by_department'],
			
            'finance_by_project' =>
			$this->resource['charts']['finance_by_project'],
			
            'delivery_forecast' =>
			$this->resource['charts']['delivery_forecast'],
			],
			];
		}
	}
