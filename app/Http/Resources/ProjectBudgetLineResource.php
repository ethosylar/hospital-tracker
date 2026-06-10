<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class ProjectBudgetLineResource extends JsonResource
	{
		public function toArray($request): array
		{
			return [
            'id' => (int)$this->id,
            'project_id' => (int)$this->project_id,
            'line_type' => $this->line_type,
            'code' => $this->code,
            'name' => $this->name,
            'planned_amount' => (float)($this->planned_amount ?? 0),
            'actual_amount' => (float)($this->actual_amount ?? 0),
            'committed_amount' => (float)($this->committed_amount ?? 0),
            'sort_order' => (int)($this->sort_order ?? 0),
            'is_active' => (bool)$this->is_active,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
			];
		}
	}	