<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class IntegrationSyncRunResource extends JsonResource
	{
		public function toArray($request): array
		{
			return [
            'id' => (int) $this->id,
			
            'external_source_id' => (int) $this->external_source_id,
            'integration_code' => $this->integration_code,
            'sync_type' => $this->sync_type,
            'status' => $this->status,
			
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
			
            'fetched_count' => (int) $this->fetched_count,
            'created_count' => (int) $this->created_count,
            'updated_count' => (int) $this->updated_count,
            'unchanged_count' => (int) $this->unchanged_count,
            'deleted_count' => (int) $this->deleted_count,
            'failed_count' => (int) $this->failed_count,
			
            'cursor_from' => $this->cursor_from,
            'cursor_to' => $this->cursor_to,
            'error_message' => $this->error_message,
			
            'triggered_by_user_id' => $this->triggered_by_user_id !== null
			? (int) $this->triggered_by_user_id
			: null,
			
            'source' => $this->whenLoaded('source', function () {
                return [
				'id' => (int) $this->source->id,
				'code' => $this->source->code,
				'name' => $this->source->name,
                ];
			}),
			
            'triggered_by' => $this->whenLoaded('triggeredBy', function () {
                return [
				'id' => (int) $this->triggeredBy->id,
				'name' => $this->triggeredBy->name,
				'email' => $this->triggeredBy->email,
                ];
			}),
			
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
			];
		}
	}	