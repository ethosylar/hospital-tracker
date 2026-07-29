<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class AmendAgreementRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			foreach (['title', 'amendment_reason'] as $field) {
				if (!$this->has($field) || $this->input($field) === null) {
					continue;
				}
				
				$value = trim((string) $this->input($field));
				$this->merge([
                $field => $value === '' ? null : $value,
				]);
			}
		}
		
		public function rules(): array
		{
			return [
            'title' => ['nullable', 'string', 'max:255'],
            'amendment_reason' => ['required', 'string'],
            'effective_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'copy_project_links' => ['nullable', 'boolean'],
			];
		}
	}	