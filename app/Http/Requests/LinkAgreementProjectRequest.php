<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class LinkAgreementProjectRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			if ($this->has('notes') && $this->notes !== null) {
				$notes = trim((string) $this->notes);
				
				$this->merge([
                'notes' => $notes === '' ? null : $notes,
				]);
			}
		}
		
		public function rules(): array
		{
			return [
            'project_id' => [
			'required',
			'integer',
			'exists:dt_projects,id',
            ],
			
            'notes' => [
			'nullable',
			'string',
			'max:5000',
            ],
			];
		}
	}	