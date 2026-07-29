<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class RenewAgreementRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			foreach (['title', 'renewal_reason', 'currency_code'] as $field) {
				if (!$this->has($field) || $this->input($field) === null) {
					continue;
				}
				
				$value = trim((string) $this->input($field));
				$this->merge([
                $field => $value === '' ? null : $value,
				]);
			}
			
			if ($this->filled('currency_code')) {
				$this->merge([
                'currency_code' => strtoupper(
				(string) $this->currency_code
                ),
				]);
			}
		}
		
		public function rules(): array
		{
			return [
            'title' => ['nullable', 'string', 'max:255'],
            'renewal_reason' => ['nullable', 'string'],
			
            'effective_date' => ['required', 'date'],
            'expiry_date' => [
			'required',
			'date',
			'after:effective_date',
            ],
			
            'contract_value' => [
			'nullable',
			'numeric',
			'min:0',
            ],
			
            'currency_code' => [
			'nullable',
			'string',
			'size:3',
			'regex:/^[A-Z]{3}$/',
            ],
			
            'notice_period_days' => [
			'nullable',
			'integer',
			'min:0',
			'max:3650',
            ],
			
            'auto_renewal' => ['nullable', 'boolean'],
            'copy_project_links' => ['nullable', 'boolean'],
			];
		}
	}	