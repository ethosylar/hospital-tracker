<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class AgreementFileResource extends JsonResource
	{
		public function toArray($request): array
		{
			$extraction = null;
			
			if ($this->relationLoaded('file') && $this->file && $this->file->relationLoaded('textExtraction')) {
				$extraction = $this->file->textExtraction;
			}
			
			return [
            'id' => (int) $this->id,
            'agreement_id' => (int) $this->agreement_id,
			
            'document_type_id' => (int) $this->document_type_id,
            'document_type' => $this->whenLoaded(
			'documentType',
			fn () => $this->documentType
			? [
			'id' => (int) $this->documentType->id,
			'code' => $this->documentType->code,
			'name' => $this->documentType->name,
			'ocr_eligible' =>
			(bool) $this->documentType->ocr_eligible,
			]
			: null
            ),
			
            'document_version' => $this->document_version,
            'document_date' => $this->document_date?->format('Y-m-d'),
            'is_current' => (bool) $this->is_current,
            'is_executed_copy' => (bool) $this->is_executed_copy,
            'supersedes_agreement_file_id' =>
			$this->supersedes_agreement_file_id
			? (int) $this->supersedes_agreement_file_id
			: null,
            'notes' => $this->notes,
			
            'file' => $this->whenLoaded(
			'file',
			fn () => $this->file
			? [
			'id' => (int) $this->file->id,
			'original_name' => $this->file->original_name,
			'mime_type' => $this->file->mime_type,
			'size' => (int) $this->file->size,
			'checksum' => $this->file->checksum,
			'uploaded_by_user_id' =>
			(int) $this->file->uploaded_by_user_id,
			'created_at' => $this->file->created_at,
			]
			: null
            ),
			
            'linked_by_user_id' => (int) $this->linked_by_user_id,
            'linked_by' => $this->whenLoaded(
			'linkedBy',
			fn () => $this->linkedBy
			? [
			'id' => (int) $this->linkedBy->id,
			'name' => $this->linkedBy->name,
			'email' => $this->linkedBy->email,
			]
			: null
            ),
			
            'ocr' => [
			'feature_enabled' => (bool) config(
			'agreement_documents.ocr.enabled',
			false
			),
			'eligible' => $this->relationLoaded('documentType')
			? (bool) $this->documentType?->ocr_eligible
			: null,
			'status' => $extraction?->status ?? 'NOT_REQUESTED',
			'engine' => $extraction?->engine,
			'language' => $extraction?->language,
			'page_count' => $extraction?->page_count,
			'requested_by_user_id' =>
			$extraction?->requested_by_user_id,
			'requested_at' => $extraction?->requested_at,
			'started_at' => $extraction?->started_at,
			'completed_at' => $extraction?->completed_at,
			'error_message' => $extraction?->error_message,
			// Extracted text is intentionally not returned in normal lists.
			'has_extracted_text' =>
			$extraction?->extracted_text !== null,
            ],
			
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
			];
		}
	}	