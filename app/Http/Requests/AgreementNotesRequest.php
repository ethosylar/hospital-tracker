<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class AgreementNotesRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			foreach (['reason','notes',] as $field) {
				if (!$this->has($field) || $this->input($field) === null) {
					continue;
				}
				$value = trim((string) $this->input($field));
				$this->merge([$field => $value === '' ? null : $value,]);
			}
		}
		
		public function rules(): array
		{
			return [
			'reason' => [
			'required',
			'string',
			'max:5000',
			],
			
			'notes' => [
			'required',
			'string',
			'max:5000',
			],
			];
		}
	}		