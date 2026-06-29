<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class ProjectPermitLinkResource extends JsonResource
	{
		public function toArray($request): array
		{
			return [
            'id' => (int) $this->id,
            'permit_id' => (int) $this->permit_id,
            'project_id' => (int) $this->project_id,
			
            'task_id' => $this->task_id !== null
			? (int) $this->task_id
			: null,
			
            'linked_by_user_id' => $this->linked_by_user_id !== null
			? (int) $this->linked_by_user_id
			: null,
			
            'linked_at' => $this->linked_at?->toISOString(),
            'notes' => $this->notes,
            'is_active' => (bool) $this->is_active,
			
            'permit' => $this->whenLoaded('permit', function () {
                return [
				'id' => (int) $this->permit->id,
				'external_form_id' => $this->permit->external_form_id,
				'external_permit_id' => $this->permit->external_permit_id,
				'normalized_status' => $this->permit->normalized_status,
				'company_name' => $this->permit->company_name,
				'exact_location' => $this->permit->exact_location,
				'work_start_date' => $this->permit->work_start_date?->format('Y-m-d'),
				'work_end_date' => $this->permit->work_end_date?->format('Y-m-d'),
                ];
			}),
			
            'project' => $this->whenLoaded('project', function () {
                return [
				'id' => (int) $this->project->id,
				'code' => $this->project->code,
				'name' => $this->project->name,
                ];
			}),
			
            'task' => $this->whenLoaded('task', function () {
                return [
				'id' => (int) $this->task->id,
				'project_id' => (int) $this->task->project_id,
				'milestone_id' => $this->task->milestone_id !== null
				? (int) $this->task->milestone_id
				: null,
				'name' => $this->task->name,
                ];
			}),
			
            'linked_by' => $this->whenLoaded('linkedBy', function () {
                return [
				'id' => (int) $this->linkedBy->id,
				'name' => $this->linkedBy->name,
				'email' => $this->linkedBy->email,
                ];
			}),
			
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
			];
		}
	}	