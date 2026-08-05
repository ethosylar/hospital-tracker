<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Validation\Rule;
	
	class StoreAgreementStatusRequest extends FormRequest
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
				$this->merge(['code' => trim($code, '_')]);
			}
			
			if ($this->has('name')) {
				$this->merge(['name' => trim((string) $this->name)]);
			}
			
			if ($this->has('description') && $this->description !== null) {
				$description = trim((string) $this->description);
				$this->merge(['description' => $description === '' ? null : $description]);
			}
		}
		
		public function rules(): array
		{
			return [
            'code' => [
			'required',
			'string',
			'max:50',
			'regex:/^[A-Z][A-Z0-9_]*$/',
			Rule::unique('st_agreement_statuses', 'code'),
            ],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_terminal' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
			];
		}
	}
