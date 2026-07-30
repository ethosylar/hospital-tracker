<?php
	
	namespace App\Http\Requests;
	
	use App\Models\FileTextExtraction;
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Validation\Rule;
	
	class AgreementFileIndexRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			foreach (['is_current', 'is_executed_copy'] as $field) {
				if (!$this->has($field)) {
					continue;
				}
				
				$value = filter_var(
                $this->input($field),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
				);
				
				if ($value !== null) {
					$this->merge([$field => $value]);
				}
			}
		}
		
		public function rules(): array
		{
			return [
            'document_type_id' => [
			'nullable',
			'integer',
			'exists:lt_agreement_document_types,id',
            ],
            'is_current' => ['nullable', 'boolean'],
            'is_executed_copy' => ['nullable', 'boolean'],
            'ocr_status' => [
			'nullable',
			'string',
			Rule::in([
			FileTextExtraction::STATUS_NOT_REQUESTED,
			FileTextExtraction::STATUS_PENDING,
			FileTextExtraction::STATUS_PROCESSING,
			FileTextExtraction::STATUS_COMPLETED,
			FileTextExtraction::STATUS_FAILED,
			]),
            ],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
			];
		}
	}	