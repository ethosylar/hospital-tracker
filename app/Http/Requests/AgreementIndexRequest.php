<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class AgreementIndexRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			foreach (['is_current_version', 'include_archived'] as $field) {
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
			
			if ($this->filled('search')) {
				$this->merge([
                'search' => trim((string) $this->search),
				]);
			}
		}
		
		public function rules(): array
		{
			return [
            'search' => ['nullable', 'string', 'max:255'],
			
            'department_id' => [
			'nullable',
			'integer',
			'exists:lt_departments,id',
            ],
			
            'owner_user_id' => [
			'nullable',
			'integer',
			'exists:users,id',
            ],
			
            'counterparty_id' => [
			'nullable',
			'integer',
			'exists:dt_counterparties,id',
            ],
			
            'agreement_category_id' => [
			'nullable',
			'integer',
			'exists:lt_agreement_categories,id',
            ],
			
            'agreement_type_id' => [
			'nullable',
			'integer',
			'exists:lt_agreement_types,id',
            ],
			
            'agreement_status_id' => [
			'nullable',
			'integer',
			'exists:st_agreement_statuses,id',
            ],
			
            'status_code' => [
			'nullable',
			'string',
			'max:50',
            ],
			
            'lifecycle_type' => [
			'nullable',
			'string',
			'in:ORIGINAL,AMENDMENT,RENEWAL',
            ],
			
            'effective_from' => ['nullable', 'date'],
            'effective_to' => [
			'nullable',
			'date',
			'after_or_equal:effective_from',
            ],
			
            'expiry_from' => ['nullable', 'date'],
            'expiry_to' => [
			'nullable',
			'date',
			'after_or_equal:expiry_from',
            ],
			
            'is_current_version' => ['nullable', 'boolean'],
            'include_archived' => ['nullable', 'boolean'],
			
            'per_page' => [
			'nullable',
			'integer',
			'min:1',
			'max:100',
            ],
			];
		}
	}	