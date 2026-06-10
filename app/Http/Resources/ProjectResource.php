<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class ProjectResource extends JsonResource
	{
		public function toArray($request): array
		{
			return [
			//Project Details
            'id' => (int)$this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'sponsor' => $this->sponsor,
            'progress' => (int)($this->progress ?? 0),
            'start_date' => $this->start_date,
            'target_end_date' => $this->target_end_date,
            'actual_end_date' => $this->actual_end_date,
			
			//Department Section
            'department' => $this->whenLoaded('department', function () {
                return [
				'id' => (int)$this->department->id,
				'code' => $this->department->code,
				'name' => $this->department->name,
                ];
			}),
			
			//Status Sections
            'status' => $this->whenLoaded('status', function () {
                return [
				'id' => (int)$this->status->id,
				'code' => $this->status->code,
				'name' => $this->status->name,
                ];
			}),
			
			//Priority Section
            'priority' => $this->whenLoaded('priority', function () {
                return [
				'id' => (int)$this->priority->id,
				'code' => $this->priority->code,
				'name' => $this->priority->name,
                ];
			}),
			
			//Owner Section
            'owner' => $this->whenLoaded('owner', function () {
                return $this->owner ? [
				'id' => (int)$this->owner->id,
				'name' => $this->owner->name,
				'email' => $this->owner->email,
                ] : null;
			}),
			
			//New Finance Section
			'currency_code' => $this->currency_code,
			'planned_cost_total' => (float)($this->planned_cost_total ?? 0),
			'actual_cost_total' => (float)($this->actual_cost_total ?? 0),
			'committed_cost_total' => (float)($this->committed_cost_total ?? 0),
			'planned_funding_total' => (float)($this->planned_funding_total ?? 0),
			'actual_funding_total' => (float)($this->actual_funding_total ?? 0),
			'budget_notes' => $this->budget_notes,
			'budget_updated_at' => $this->budget_updated_at,
			
			// derived (helpful for dashboard UI)
			'cost_utilization_pct' => ($this->planned_cost_total ?? 0) > 0
			? round(((float)$this->actual_cost_total / (float)$this->planned_cost_total) * 100, 1)
			: null,
			'cost_variance' => (float)($this->planned_cost_total ?? 0) - (float)($this->actual_cost_total ?? 0),
			
			'funding_utilization_pct' => ($this->planned_funding_total ?? 0) > 0
			? round(((float)$this->actual_funding_total / (float)$this->planned_funding_total) * 100, 1)
			: null,
			'funding_variance' => (float)($this->planned_funding_total ?? 0) - (float)($this->actual_funding_total ?? 0),
			
			//Audit
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
			];
		}
	}
