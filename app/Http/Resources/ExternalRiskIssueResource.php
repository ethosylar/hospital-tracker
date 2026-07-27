<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class ExternalRiskIssueResource extends JsonResource
	{
		public function toArray($request): array
		{
			$includePayload =
            $request->boolean('include_payload') ||
            $request->route('issue') !== null;
			
			$raw = $this->raw_payload;
			$decodedPayload = null;
			
			if ($includePayload && is_string($raw) && $raw !== '') {
				$try = json_decode($raw, true);
				$decodedPayload = (json_last_error() === JSON_ERROR_NONE) ? $try : $raw;
				} elseif ($includePayload && $raw === null) {
				$decodedPayload = null;
			}
			
			return [
            'id' => (int) $this->id,
			
            'external_source_id' => $this->external_source_id ? (int) $this->external_source_id : null,
            'external_source_code' => optional($this->externalSource)->code,
            'external_source_name' => optional($this->externalSource)->name,
			
            'external_id' => $this->external_id,
			
            'project_id' => $this->project_id ? (int) $this->project_id : null,
            'project_code' => optional($this->project)->code,
            'project_name' => optional($this->project)->name,
			
            'type_id' => $this->type_id ? (int) $this->type_id : null,
            'type_code' => optional($this->type)->code,
            'type_name' => optional($this->type)->name,
			
            'title' => $this->title,
            'description' => $this->description,
			
            'severity_id' => $this->severity_id ? (int) $this->severity_id : null,
            'severity_code' => optional($this->severity)->code,
            'severity_name' => optional($this->severity)->name,
			
            'risk_issue_status_id' => $this->risk_issue_status_id ? (int) $this->risk_issue_status_id : null,
            'risk_issue_status_code' => optional($this->status)->code,
            'risk_issue_status_name' => optional($this->status)->name,
			
            'owner' => $this->owner,
			
            'source_created_at' => $this->source_created_at,
            'source_updated_at' => $this->source_updated_at,
            'last_synced_at' => $this->last_synced_at,
			
            'links' => ExternalRiskIssueLinkResource::collection($this->whenLoaded('links')),
            'active_links' => ExternalRiskIssueLinkResource::collection($this->whenLoaded('activeLinks')),
            'active_link_count' => $this->whenLoaded('activeLinks', fn () => $this->activeLinks->count()),
			
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
			
            'raw_payload' => $this->when($includePayload, $decodedPayload),
			];
		}
	}
