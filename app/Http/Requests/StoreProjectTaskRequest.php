<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class StoreProjectTaskRequest extends FormRequest
	{
		public function authorize(): bool
		{
			// already protected by route middleware role:PMO,PM
			return true;
		}
		
		public function rules(): array
		{
			return [
			'parent_task_id' => ['nullable','integer','exists:dt_project_tasks,id'],
			'depends_on_task_id' => ['nullable','integer','exists:dt_project_tasks,id'],
			
			'name' => ['required','string','max:255'],
			'description' => ['nullable','string'],
			
			'task_color' => ['nullable','string','max:20','regex:/^#?[0-9A-Fa-f]{6}$/'],
			
			'task_status_id' => ['required','integer','exists:st_task_statuses,id'],
			'actual_task_status_id' => ['nullable','integer','exists:st_task_statuses,id'],
			
			'milestone_id' => ['nullable','integer','exists:dt_project_milestones,id'],
			
			'progress' => ['nullable','integer','min:0','max:100'],
			
			'start_date' => ['nullable','date'],
			'end_date' => ['nullable','date','after_or_equal:start_date'],
			
			'actual_start_date' => ['nullable','date'],
			'actual_end_date' => ['nullable','date','after_or_equal:actual_start_date'],
			
			'duration' => ['nullable','integer','min:0'],
			
			'assigned_to_user_id' => ['nullable','integer','exists:users,id'],
			'sort_order' => ['nullable','integer','min:0'],
			];
		}
		
	}
