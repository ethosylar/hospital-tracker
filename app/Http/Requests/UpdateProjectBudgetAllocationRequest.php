<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class UpdateProjectBudgetAllocationRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
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
            'budget_line_id' => ['sometimes','integer','exists:dt_project_budget_lines,id'],
			
            'task_id' => ['sometimes','nullable','integer','exists:dt_project_tasks,id'],
            'milestone_id' => ['sometimes','nullable','integer','exists:dt_project_milestones,id'],
			
            'planned_amount' => ['sometimes','nullable','numeric','min:0'],
            'actual_amount' => ['sometimes','nullable','numeric','min:0'],
            'committed_amount' => ['sometimes','nullable','numeric','min:0'],
			
            'sort_order' => ['sometimes','nullable','integer','min:0'],
            'is_active' => ['sometimes','nullable','boolean'],
            'notes' => ['sometimes','nullable','string'],
			];
		}
		
		public function withValidator($validator)
		{
			$validator->after(function ($v) {
				// only enforce "at least one target" when either was provided
				if (!$this->has('task_id') && !$this->has('milestone_id')) return;
				
				$taskId = $this->input('task_id');
				$milestoneId = $this->input('milestone_id');
				
				if (empty($taskId) && empty($milestoneId)) {
					$v->errors()->add('task_id', 'Either task_id or milestone_id is required.');
					$v->errors()->add('milestone_id', 'Either task_id or milestone_id is required.');
				}
			});
		}
	}	