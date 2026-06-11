<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class ProjectBudgetAllocationResource extends JsonResource
	{
		public function toArray($request): array
		{
			return [
			'id' => (int)$this->id,
			'project_id' => (int)$this->project_id,
			'budget_line_id' => (int)$this->budget_line_id,
			'task_id' => $this->task_id ? (int)$this->task_id : null,
			'milestone_id' => $this->milestone_id ? (int)$this->milestone_id : null,
			
			'planned_amount' => (float)($this->planned_amount ?? 0),
			'actual_amount' => (float)($this->actual_amount ?? 0),
			'committed_amount' => (float)($this->committed_amount ?? 0),
			
			'sort_order' => (int)($this->sort_order ?? 0),
			'is_active' => (bool)$this->is_active,
			'notes' => $this->notes,
			
			'budget_line' => $this->whenLoaded('line', function () {
                return [
                    'id' => (int)$this->line->id,
                    'line_type' => $this->line->line_type,
                    'code' => $this->line->code,
                    'name' => $this->line->name,
                ];
            }),
			
			'created_at' => $this->created_at,
			'updated_at' => $this->updated_at,
			];
		}
	}	