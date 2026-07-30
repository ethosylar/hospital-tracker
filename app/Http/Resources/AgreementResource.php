<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class AgreementResource extends JsonResource
	{
		public function toArray($request): array
		{
			return [
            'id' => (int) $this->id,
            'agreement_no' => $this->agreement_no,
            'title' => $this->title,
			
            'department_id' => (int) $this->department_id,
            'department' => $this->whenLoaded(
			'department',
			fn () => $this->department ? [
			'id' => (int) $this->department->id,
			'code' => $this->department->code,
			'name' => $this->department->name,
			] : null
            ),
			
            'owner_user_id' => (int) $this->owner_user_id,
            'owner' => $this->whenLoaded(
			'owner',
			fn () => $this->owner
			? [
			'id' => (int) $this->owner->id,
			'name' => $this->owner->name,
			'email' => $this->owner->email,
			] : null
            ),
			
            'counterparty_id' => (int) $this->counterparty_id,
            'counterparty' => $this->whenLoaded(
			'counterparty',
			fn () => $this->counterparty
			? [
			'id' => (int) $this->counterparty->id,
			'code' => $this->counterparty->code,
			'counterparty_type' =>
			$this->counterparty->counterparty_type,
			'legal_name' =>
			$this->counterparty->legal_name,
			'trading_name' =>
			$this->counterparty->trading_name,
			'registration_no' =>
			$this->counterparty->registration_no,
			] : null
            ),
			
            'agreement_category_id' =>
			(int) $this->agreement_category_id,
			
            'category' => $this->whenLoaded(
			'category',
			fn () => $this->category
			? [
			'id' => (int) $this->category->id,
			'code' => $this->category->code,
			'name' => $this->category->name,
			] : null
            ),
			
            'agreement_type_id' => $this->agreement_type_id
			? (int) $this->agreement_type_id
			: null,
			
            'type' => $this->whenLoaded(
			'type',
			fn () => $this->type
			? [
			'id' => (int) $this->type->id,
			'code' => $this->type->code,
			'name' => $this->type->name,
			] : null
            ),
			
            'agreement_status_id' =>
			(int) $this->agreement_status_id,
			
            'status' => $this->whenLoaded(
			'status',
			fn () => $this->status
			? [
			'id' => (int) $this->status->id,
			'code' => $this->status->code,
			'name' => $this->status->name,
			'is_terminal' =>
			(bool) $this->status->is_terminal,
			] : null
            ),
			
            'description' => $this->description,
            'purpose' => $this->purpose,
            'scope' => $this->scope,
			
            'effective_date' => $this->effective_date?->format('Y-m-d'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'signed_date' => $this->signed_date?->format('Y-m-d'),
            'notice_period_days' => $this->notice_period_days,
            'auto_renewal' => (bool) $this->auto_renewal,
			
            'contract_value' => $this->contract_value,
            'currency_code' => $this->currency_code,
			
            'lifecycle' => [
			'type' => $this->lifecycle_type,
			'parent_agreement_id' => $this->parent_agreement_id
			? (int) $this->parent_agreement_id : null,
			'root_agreement_id' => $this->root_agreement_id
			? (int) $this->root_agreement_id : null,
			'revision_no' => (int) $this->revision_no,
			'renewal_sequence' => (int) $this->renewal_sequence,
			'is_current_version' =>
			(bool) $this->is_current_version,
            ],
			
            'parent_agreement' => $this->whenLoaded(
			'parentAgreement',
			fn () => $this->parentAgreement
			? [
			'id' => (int) $this->parentAgreement->id,
			'agreement_no' =>
			$this->parentAgreement->agreement_no,
			'title' => $this->parentAgreement->title,
			] : null
            ),
			
            'child_agreements' => $this->whenLoaded(
			'childAgreements',
			fn () => $this->childAgreements->map(
			fn ($agreement) => [
			'id' => (int) $agreement->id,
			'agreement_no' => $agreement->agreement_no,
			'title' => $agreement->title,
			'lifecycle_type' =>
			$agreement->lifecycle_type,
			'revision_no' =>
			(int) $agreement->revision_no,
			'renewal_sequence' =>
			(int) $agreement->renewal_sequence,
			'is_current_version' =>
			(bool) $agreement->is_current_version,
			]
			)
            ),
			
            'workflow' => [
			'submitted_at' => $this->submitted_at,
			'submitted_by_user_id' =>
			$this->submitted_by_user_id,
			'approved_at' => $this->approved_at,
			'approved_by_user_id' =>
			$this->approved_by_user_id,
			'terminated_on' =>
			$this->terminated_on?->format('Y-m-d'),
			'termination_reason' =>
			$this->termination_reason,
			'terminated_by_user_id' =>
			$this->terminated_by_user_id,
			'archived_at' => $this->archived_at,
			'archived_by_user_id' =>
			$this->archived_by_user_id,
            ],
			
            'projects' => $this->whenLoaded(
			'projects',
			fn () => $this->projects->map(
			fn ($project) => [
			'link_id' => (int) $project->pivot->id,
			'id' => (int) $project->id,
			'code' => $project->code,
			'name' => $project->name,
			'notes' => $project->pivot->notes,
			'linked_by_user_id' =>
			$project->pivot->linked_by_user_id,
			'linked_at' =>
			$project->pivot->created_at,
			]
			)
            ),
			
            'documents' => $this->whenLoaded(
			'documents',
			fn () => AgreementFileResource::collection(
			$this->documents
			)
            ),
			
            'lifecycle_events' => $this->whenLoaded(
			'lifecycleEvents',
			fn () => AgreementLifecycleEventResource::collection(
			$this->lifecycleEvents
			)
            ),
			
            'created_by_user_id' =>
			(int) $this->created_by_user_id,
            'updated_by_user_id' => $this->updated_by_user_id
			? (int) $this->updated_by_user_id : null,
			
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
			];
		}
	}	