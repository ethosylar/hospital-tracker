<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class ProjectTaskGanttResource extends JsonResource
	{
		public function toArray($request): array
		{
			
			$budget = $this->getAttribute('budget') ?? [
			'planned_cost' => 0.0,
			'actual_cost' => 0.0,
			'committed_cost' => 0.0,
			'spent_cost' => 0.0,
			'planned_funding' => 0.0,
			'actual_funding' => 0.0,
			'committed_funding' => 0.0,
			'received_funding' => 0.0,
			'net_spent' => 0.0,
			];
			
			return [
			'id' => (int)$this->id,
			'project_id' => (int)$this->project_id,
			
			'parent_task_id' => $this->parent_task_id ? (int)$this->parent_task_id : null,
			'depends_on_task_id' => $this->depends_on_task_id ? (int)$this->depends_on_task_id : null,
			
			'name' => $this->name,
			'description' => $this->description,
			
			'task_color' => $this->task_color,
			
			'progress' => (int)($this->progress ?? 0),
			
			'start_date' => $this->start_date?->format('Y-m-d'),
			'end_date' => $this->end_date?->format('Y-m-d'),
			
			'actual_start_date' => $this->actual_start_date?->format('Y-m-d'),
			'actual_end_date' => $this->actual_end_date?->format('Y-m-d'),
			
			'milestone_id' => $this->milestone_id ? (int)$this->milestone_id : null,
			'milestone' => $this->whenLoaded('milestone', function () {
                if (!$this->milestone) return null;
				
                return [
				'id' => (int)$this->milestone->id,
				'project_id' => (int)$this->milestone->project_id,
				'name' => $this->milestone->name,
				'milestone_date' => $this->milestone->milestone_date?->format('Y-m-d'),
				'status' => $this->milestone->status,
                ];
			}),
			
			'duration' => (int)($this->duration ?? 0),
			
			'sort_order' => (int)($this->sort_order ?? 0),
			
			'budget' => $budget,
			
			'task_status_id' => (int)$this->task_status_id,
			'status_code' => $this->whenLoaded('status', fn() => $this->status?->code),
			'status_name' => $this->whenLoaded('status', fn() => $this->status?->name),
			
			'actual_task_status_id' => $this->actual_task_status_id ? (int)$this->actual_task_status_id : null,
			'actual_status_code' => $this->whenLoaded('actualStatus', fn() => $this->actualStatus?->code),
			'actual_status_name' => $this->whenLoaded('actualStatus', fn() => $this->actualStatus?->name),
			
			'assigned_to_user_id' => $this->assigned_to_user_id ? (int)$this->assigned_to_user_id : null,
			'assigned_to_name' => $this->whenLoaded('assignedTo', fn() => $this->assignedTo?->name),
			];
			
		}
	}
