<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Validation\Rule;
	
	class UpdateAgreementDocumentTypeRequest extends FormRequest
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
			$documentType = $this->route('documentType');
			$documentTypeId = is_object($documentType)
            ? $documentType->id
            : $documentType;
			
			return [
            'code' => [
			'sometimes',
			'required',
			'string',
			'max:60',
			'regex:/^[A-Z][A-Z0-9_]*$/',
			Rule::unique('lt_agreement_document_types', 'code')
			->ignore($documentTypeId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string'],
            'ocr_eligible' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
			];
		}
	}	