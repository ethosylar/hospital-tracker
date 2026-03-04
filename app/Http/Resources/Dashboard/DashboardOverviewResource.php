<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardOverviewResource extends JsonResource
{
    public function toArray($request): array
    {
        // $this->resource is an array coming from controller
        return [
            'counts' => $this->resource['counts'],
            'delayed_projects' => ProjectMiniResource::collection($this->resource['delayed_projects']),
            'upcoming_milestones' => MilestoneMiniResource::collection($this->resource['upcoming_milestones']),
            'charts' => [
                'projects_by_status' => ChartItemResource::collection($this->resource['charts']['projects_by_status']),
                'projects_by_department' => ChartItemResource::collection($this->resource['charts']['projects_by_department']),
                'projects_by_priority' => ChartItemResource::collection($this->resource['charts']['projects_by_priority']),
                'progress_distribution' => $this->resource['charts']['progress_distribution'], // already array
                'tasks_by_status' => ChartItemResource::collection($this->resource['charts']['tasks_by_status']),
                'project_timeline_health' => $this->resource['charts']['project_timeline_health'], // already array
            ],
        ];
    }
}
