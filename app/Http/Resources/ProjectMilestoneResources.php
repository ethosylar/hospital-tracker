<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class ProjectMilestoneResources extends JsonResource
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
            'id' => $this->id,
            'project_id' => $this->project_id,
            'name' => $this->name,
            'milestone_date' => optional($this->milestone_date)->toDateString(),
            'status' => $this->status,
			'budget' => $budget,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
			];
		}
	}
