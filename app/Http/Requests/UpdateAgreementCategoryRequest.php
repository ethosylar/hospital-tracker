<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Validation\Rule;
	
	class UpdateAgreementCategoryRequest extends FormRequest
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
		}
		
		public function rules(): array
		{
			$status = $this->route('category');
			$id = is_object($status) ? $status->id : $status;
			
			return [
            'code' => [
			'sometimes',
			'required',
			'string',
			'max:60',
			'regex:/^[A-Z][A-Z0-9_]*$/',
			Rule::unique('lt_agreement_categories', 'code')
			->ignore($id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
			];
		}
	}	