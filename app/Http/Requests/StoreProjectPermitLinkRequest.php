<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class StoreProjectPermitLinkRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			if (!$this->has('task_ids') || $this->task_ids === null) {
				$this->merge([
                'task_ids' => [],
				]);
			}
			
			if ($this->has('notes') && $this->notes !== null) {
				$this->merge([
                'notes' => trim((string) $this->notes),
				]);
			}
		}
		
		public function rules(): array
		{
			return [
            /*
				* Internal Hospital Tracker ID:
				* dt_external_permits.id
			*/
            'permit_id' => [
			'required',
			'integer',
			'exists:dt_external_permits,id',
            ],
			
            /*
				* Empty array means project-level link.
			*/
            'task_ids' => ['required', 'array'],
			
            'task_ids.*' => [
			'integer',
			'distinct',
			'exists:dt_project_tasks,id',
            ],
			
            'notes' => ['nullable', 'string', 'max:2000'],
			];
		}
	}	