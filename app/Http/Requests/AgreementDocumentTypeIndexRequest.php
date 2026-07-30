<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class AgreementDocumentTypeIndexRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			foreach (['is_active', 'ocr_eligible'] as $field) {
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
            'is_active' => ['nullable', 'boolean'],
            'ocr_eligible' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
			];
		}
	}	