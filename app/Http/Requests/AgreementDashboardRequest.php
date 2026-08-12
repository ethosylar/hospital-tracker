<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class AgreementDashboardRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			foreach (['timeline_all', 'include_no_expiry'] as $field) {
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
            'timeline_months' => [
			'nullable',
			'integer',
			'min:1',
			'max:36',
            ],
            'timeline_all' => [
			'nullable',
			'boolean',
            ],
            'include_no_expiry' => [
			'nullable',
			'boolean',
            ],
            'timeline_limit' => [
			'nullable',
			'integer',
			'min:1',
			'max:1000',
            ],
            'recent_limit' => [
			'nullable',
			'integer',
			'min:1',
			'max:50',
            ],
			
            // Optional dashboard filters. These only narrow records already
            // allowed by the user's Agreement visibility permissions.
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
			];
		}
	}	