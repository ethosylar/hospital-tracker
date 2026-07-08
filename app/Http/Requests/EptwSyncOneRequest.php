<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class EptwSyncOneRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			if ($this->has('external_form_id')) {
				$this->merge([
                'external_form_id' => trim((string) $this->external_form_id),
				]);
			}
		}
		
		public function rules(): array
		{
			return [
            'external_form_id' => [
			'required',
			'string',
			'max:50',
            ],
			
            'run_async' => ['nullable', 'boolean'],
			];
		}
	}	