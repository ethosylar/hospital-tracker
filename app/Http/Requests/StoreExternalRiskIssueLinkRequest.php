<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class StoreExternalRiskIssueLinkRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		public function rules(): array
		{
			return [
            'project_id' => ['nullable', 'integer', 'exists:dt_projects,id'],
            'task_id' => ['nullable', 'integer', 'exists:dt_project_tasks,id'],
            'milestone_id' => ['nullable', 'integer', 'exists:dt_project_milestones,id'],
            'permit_id' => ['nullable', 'integer', 'exists:dt_external_permits,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
			];
		}
		
		public function withValidator($validator): void
		{
			$validator->after(function ($validator) {
				if (
                !$this->filled('project_id') &&
                !$this->filled('task_id') &&
                !$this->filled('milestone_id') &&
                !$this->filled('permit_id')
				) {
					$validator->errors()->add(
                    'link',
                    'At least one link target is required: project_id, task_id, milestone_id, or permit_id.'
					);
				}
			});
		}
	}
