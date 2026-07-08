<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Validation\Rule;
	
	class EptwSyncRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			if (!$this->has('mode') || $this->mode === null) {
				$this->merge([
                'mode' => 'INCREMENTAL',
				]);
			}
			
			if ($this->has('mode')) {
				$this->merge([
                'mode' => strtoupper(trim((string) $this->mode)),
				]);
			}
		}
		
		public function rules(): array
		{
			return [
            'mode' => [
			'required',
			Rule::in(['FULL', 'INCREMENTAL', 'MANUAL']),
            ],
			
            /*
				* true = queue background job
				* false = run immediately and return sync result
			*/
            'run_async' => ['nullable', 'boolean'],
			];
		}
	}	