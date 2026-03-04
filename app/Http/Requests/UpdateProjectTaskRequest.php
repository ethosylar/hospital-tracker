<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class UpdateProjectTaskRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		public function rules(): array
		{
			return [
			'parent_task_id' => ['sometimes','nullable','integer','exists:dt_project_tasks,id'],
			'depends_on_task_id' => ['sometimes','nullable','integer','exists:dt_project_tasks,id'],
			
			'name' => ['sometimes','string','max:255'],
			'description' => ['sometimes','nullable','string'],
			
			'task_color' => ['sometimes','nullable','string','max:20','regex:/^#?[0-9A-Fa-f]{6}$/'],
			
			'task_status_id' => ['sometimes','integer','exists:st_task_statuses,id'],
			'actual_task_status_id' => ['sometimes','nullable','integer','exists:st_task_statuses,id'],
			
			'milestone_id' => ['sometimes','nullable','integer','exists:dt_project_milestones,id'],
			
			'progress' => ['sometimes','nullable','integer','min:0','max:100'],
			
			'start_date' => ['sometimes','nullable','date'],
			'end_date' => ['sometimes','nullable','date','after_or_equal:start_date'],
			
			'actual_start_date' => ['sometimes','nullable','date'],
			'actual_end_date' => ['sometimes','nullable','date','after_or_equal:actual_start_date'],
			
			'duration' => ['sometimes','nullable','integer','min:0'],
			
			'assigned_to_user_id' => ['sometimes','nullable','integer','exists:users,id'],
			'sort_order' => ['sometimes','nullable','integer','min:0'],
			];
		}
		
	}
