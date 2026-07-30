<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Validation\Rule;
	
	class StoreAgreementFileRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			$this->normaliseBooleans();
			
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
            'file' => [
			'required',
			'file',
			'max:25600',
			'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,txt',
            ],
            'document_type_id' => [
			'required',
			'integer',
			Rule::exists('lt_agreement_document_types', 'id')
			->where(fn ($query) => $query->where('is_active', true)),
            ],
            'document_version' => ['nullable', 'string', 'max:80'],
            'document_date' => ['nullable', 'date'],
            'is_current' => ['nullable', 'boolean'],
            'is_executed_copy' => ['nullable', 'boolean'],
            'supersedes_agreement_file_id' => [
			'nullable',
			'integer',
			'exists:dt_agreement_files,id',
            ],
            'notes' => ['nullable', 'string'],
			];
		}
		
		private function normaliseBooleans(): void
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
	}	