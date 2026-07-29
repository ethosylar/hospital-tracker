<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Validation\Rule;
	
	class StoreAgreementTypeRequest extends FormRequest
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
			return [
            'agreement_category_id' => [
			'required',
			'integer',
			Rule::exists('lt_agreement_categories', 'id')
			->where(fn ($query) => $query->where('is_active', true)),
            ],
            'code' => [
			'required',
			'string',
			'max:60',
			'regex:/^[A-Z][A-Z0-9_]*$/',
			Rule::unique('lt_agreement_types', 'code'),
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
			];
		}
	}		