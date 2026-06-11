<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class StoreProjectBudgetAllocationRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true; // route middleware role:PMO,PM / ADMIN handles access
		}
		
		protected function prepareForValidation(): void
		{
			foreach (['task_id','milestone_id','budget_line_id','sort_order'] as $k) {
				if ($this->has($k) && $this->{$k} === '') $this->merge([$k => null]);
			}
		}
		
		public function rules(): array
		{
			return [
            'budget_line_id' => ['required','integer','exists:dt_project_budget_lines,id'],
			
            'task_id' => ['nullable','integer','exists:dt_project_tasks,id'],
            'milestone_id' => ['nullable','integer','exists:dt_project_milestones,id'],
			
            'planned_amount' => ['nullable','numeric','min:0'],
            'actual_amount' => ['nullable','numeric','min:0'],
            'committed_amount' => ['nullable','numeric','min:0'],
			
            'sort_order' => ['nullable','integer','min:0'],
            'is_active' => ['nullable','boolean'],
            'notes' => ['nullable','string'],
			];
		}
		
		public function withValidator($validator)
		{
			$validator->after(function ($v) {
				$taskId = $this->input('task_id');
				$milestoneId = $this->input('milestone_id');
				
				if (empty($taskId) && empty($milestoneId)) {
					$v->errors()->add('task_id', 'Either task_id or milestone_id is required.');
					$v->errors()->add('milestone_id', 'Either task_id or milestone_id is required.');
				}
			});
		}
	}		