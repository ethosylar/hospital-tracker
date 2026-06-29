<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class ExternalPermitResource extends JsonResource
	{
		public function toArray($request): array
		{
			$closedStatuses = ['COMPLETED', 'CANCELLED'];
			
			$isExpired = $this->work_end_date !== null
            && $this->work_end_date->lt(today())
            && !in_array($this->normalized_status, $closedStatuses, true);
			
			$daysUntilEnd = $this->work_end_date !== null
            ? (int) today()->diffInDays($this->work_end_date, false)
            : null;
			
			return [
            'id' => (int) $this->id,
			
            'external_source_id' => (int) $this->external_source_id,
            'external_form_id' => $this->external_form_id,
            'external_permit_id' => $this->external_permit_id,
			
            'raw_status' => $this->raw_status,
            'normalized_status' => $this->normalized_status,
			
            'applicant_name' => $this->applicant_name,
            'service_name' => $this->service_name,
            'company_name' => $this->company_name,
            'supervisor_name' => $this->supervisor_name,
            'exact_location' => $this->exact_location,
			
            'work_type' => $this->work_type,
            'hazards' => $this->hazards,
            'ppe' => $this->ppe,
            'worksite_controls' => $this->worksite_controls,
            'infection_controls' => $this->infection_controls,
            'remark' => $this->remark,
			
            'work_start_date' => $this->work_start_date?->format('Y-m-d'),
            'work_end_date' => $this->work_end_date?->format('Y-m-d'),
            'work_start_time' => $this->work_start_time,
            'work_end_time' => $this->work_end_time,
			
            'brief_date' => $this->brief_date?->format('Y-m-d'),
            'brief_time' => $this->brief_time,
            'brief_conducted_by' => $this->brief_conducted_by,
			
            'source_created_at' => $this->source_created_at?->toISOString(),
            'source_updated_at' => $this->source_updated_at?->toISOString(),
            'last_synced_at' => $this->last_synced_at?->toISOString(),
            'last_seen_at' => $this->last_seen_at?->toISOString(),
			
            'source_url' => $this->source_url,
			
            'is_source_deleted' => (bool) $this->is_source_deleted,
            'source_deleted_at' => $this->source_deleted_at?->toISOString(),
			
            'is_expired' => $isExpired,
            'days_until_end' => $daysUntilEnd,
			
            'active_links_count' => isset($this->active_links_count)
			? (int) $this->active_links_count
			: null,
			
            'source' => $this->whenLoaded('source', function () {
                return [
				'id' => (int) $this->source->id,
				'code' => $this->source->code,
				'name' => $this->source->name,
				'base_url' => $this->source->base_url,
                ];
			}),
			
            'links' => ProjectPermitLinkResource::collection(
			$this->whenLoaded('links')
            ),
			
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
			];
		}
	}	