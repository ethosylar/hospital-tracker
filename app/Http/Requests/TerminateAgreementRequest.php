<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class TerminateAgreementRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			if ($this->has('termination_reason')) {
				$this->merge([
                'termination_reason' => trim(
				(string) $this->termination_reason
                ),
				]);
			}
		}
		
		public function rules(): array
		{
			return [
            'termination_reason' => [
			'required',
			'string',
			'max:5000',
            ],
			
            'terminated_on' => [
			'nullable',
			'date',
            ],
			];
		}
	}	