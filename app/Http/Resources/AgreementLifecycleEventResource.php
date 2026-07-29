<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class AgreementLifecycleEventResource extends JsonResource
	{
		public function toArray($request): array
		{
			return [
            'id' => (int) $this->id,
            'agreement_id' => (int) $this->agreement_id,
            'event_type' => $this->event_type,
			
            'from_status' => $this->whenLoaded(
			'fromStatus',
			fn () => $this->fromStatus
			? [
			'id' => (int) $this->fromStatus->id,
			'code' => $this->fromStatus->code,
			'name' => $this->fromStatus->name,
			]
			: null
            ),
			
            'to_status' => $this->whenLoaded(
			'toStatus',
			fn () => $this->toStatus
			? [
			'id' => (int) $this->toStatus->id,
			'code' => $this->toStatus->code,
			'name' => $this->toStatus->name,
			]
			: null
            ),
			
            'related_agreement' => $this->whenLoaded(
			'relatedAgreement',
			fn () => $this->relatedAgreement
			? [
			'id' => (int) $this->relatedAgreement->id,
			'agreement_no' =>
			$this->relatedAgreement->agreement_no,
			'title' => $this->relatedAgreement->title,
			]
			: null
            ),
			
            'performed_by' => $this->whenLoaded(
			'performedBy',
			fn () => $this->performedBy
			? [
			'id' => (int) $this->performedBy->id,
			'name' => $this->performedBy->name,
			'email' => $this->performedBy->email,
			]
			: null
            ),
			
            'reason' => $this->reason,
            'metadata' => $this->metadata,
            'event_at' => $this->event_at,
            'created_at' => $this->created_at,
			];
		}
	}	