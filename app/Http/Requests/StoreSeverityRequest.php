<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class StoreSeverityRequest extends FormRequest
	{
		public function authorize(): bool
		{
			// You already protect routes with middleware role:ADMIN,
			// so keep this true (no accidental 403).
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			$this->merge([
            'code' => $this->has('code') ? strtoupper(trim((string)$this->input('code'))) : null,
            'name' => $this->has('name') ? trim((string)$this->input('name')) : null,
			]);
		}
		
		public function rules(): array
		{
			return [
            'code' => ['required','string','max:50','unique:st_severities,code'],
            'name' => ['required','string','max:150'],
            'sort_order' => ['nullable','integer','min:0'],
            'is_active' => ['nullable','boolean'],
			];
		}
	}
