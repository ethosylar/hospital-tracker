<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Validation\Rule;
	
	class UpdateAgreementFileRequest extends FormRequest
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
			
			foreach (['document_version', 'notes'] as $field) {
				if (!$this->has($field) || $this->input($field) === null) {
					continue;
				}
				
				$value = trim((string) $this->input($field));
				$this->merge([$field => $value === '' ? null : $value]);
			}
		}
		
		public function rules(): array
		{
			return [
            'document_type_id' => [
			'sometimes',
			'required',
			'integer',
			Rule::exists('lt_agreement_document_types', 'id')
			->where(fn ($query) => $query->where('is_active', true)),
            ],
            'document_version' => ['sometimes', 'nullable', 'string', 'max:80'],
            'document_date' => ['sometimes', 'nullable', 'date'],
            'is_current' => ['sometimes', 'boolean'],
            'is_executed_copy' => ['sometimes', 'boolean'],
            'supersedes_agreement_file_id' => [
			'sometimes',
			'nullable',
			'integer',
			'exists:dt_agreement_files,id',
            ],
            'notes' => ['sometimes', 'nullable', 'string'],
			];
		}
	}	