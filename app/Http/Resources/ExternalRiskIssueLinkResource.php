<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class ExternalRiskIssueLinkResource extends JsonResource
	{
		public function toArray($request): array
		{
			return [
            'id' => (int) $this->id,
            'external_risk_issue_id' => (int) $this->external_risk_issue_id,
			
            'project_id' => $this->project_id ? (int) $this->project_id : null,
            'project_code' => optional($this->project)->code,
            'project_name' => optional($this->project)->name,
			
            'task_id' => $this->task_id ? (int) $this->task_id : null,
            'task_title' => optional($this->task)->title,
            'task_name' => optional($this->task)->name,
			
            'milestone_id' => $this->milestone_id ? (int) $this->milestone_id : null,
            'milestone_name' => optional($this->milestone)->name,
            'milestone_date' => optional($this->milestone)->milestone_date,
			
            'permit_id' => $this->permit_id ? (int) $this->permit_id : null,
            'permit_external_form_id' => optional($this->permit)->external_form_id,
            'permit_external_permit_id' => optional($this->permit)->external_permit_id,
            'permit_status' => optional($this->permit)->normalized_status,
			
            'linked_by_user_id' => $this->linked_by_user_id ? (int) $this->linked_by_user_id : null,
            'linked_by_name' => optional($this->linkedBy)->name,
            'linked_at' => $this->linked_at,
            'notes' => $this->notes,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
			];
		}
	}
