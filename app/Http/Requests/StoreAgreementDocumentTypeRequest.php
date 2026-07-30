<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Validation\Rule;
	
	class StoreAgreementDocumentTypeRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			if ($this->has('code')) {
				$code = strtoupper(trim((string) $this->code));
				$code = preg_replace('/[^A-Z0-9]+/', '_', $code) ?? $code;
				
				$this->merge([
                'code' => trim($code, '_'),
				]);
			}
			
			if ($this->has('name')) {
				$this->merge([
                'name' => trim((string) $this->name),
				]);
			}
			
			if ($this->has('description') && $this->description !== null) {
				$description = trim((string) $this->description);
				
				$this->merge([
                'description' => $description === '' ? null : $description,
				]);
			}
		}
		
		public function rules(): array
		{
			return [
            'code' => [
			'required',
			'string',
			'max:60',
			'regex:/^[A-Z][A-Z0-9_]*$/',
			Rule::unique('lt_agreement_document_types', 'code'),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'ocr_eligible' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
			];
		}
	}	