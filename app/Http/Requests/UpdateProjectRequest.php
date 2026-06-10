<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class UpdateProjectRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		public function rules(): array
		{
			$id = $this->route('project');
			
			return [
            'code' => ['sometimes','string','max:50',"unique:dt_projects,code,{$id}"],
            'name' => ['sometimes','string','max:255'],
            'description' => ['nullable','string'],
			
            'department_id' => ['sometimes','integer','exists:lt_departments,id'],
            'owner_user_id' => ['nullable','integer','exists:users,id'],
            'sponsor' => ['nullable','string','max:255'],
			
            'project_status_id' => ['sometimes','integer','exists:st_project_statuses,id'],
            'priority_id' => ['sometimes','integer','exists:lt_priorities,id'],
			
            'progress' => ['nullable','integer','min:0','max:100'],
            'start_date' => ['nullable','date'],
            'target_end_date' => ['nullable','date'],
            'actual_end_date' => ['nullable','date'],
			
			'currency_code' => ['sometimes','nullable','string','size:3'],
			'planned_cost_total' => ['sometimes','nullable','numeric','min:0'],
			'actual_cost_total' => ['sometimes','nullable','numeric','min:0'],
			'committed_cost_total' => ['sometimes','nullable','numeric','min:0'],
			'planned_funding_total' => ['sometimes','nullable','numeric','min:0'],
			'actual_funding_total' => ['sometimes','nullable','numeric','min:0'],
			'budget_notes' => ['sometimes','nullable','string'],
			'budget_updated_at' => ['sometimes','nullable','date'],
			];
		}
	}
