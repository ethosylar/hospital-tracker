<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class StoreProjectRequest extends FormRequest
	{
		public function authorize(): bool
		{
			// access is already controlled by route middleware (role:PMO,PM)
			return true;
		}
		
		public function rules(): array
		{
			return [
            'code' => ['required','string','max:50','unique:dt_projects,code'],
            'name' => ['required','string','max:255'],
            'description' => ['nullable','string'],
			
            'department_id' => ['required','integer','exists:lt_departments,id'],
            'owner_user_id' => ['nullable','integer','exists:users,id'],
            'sponsor' => ['nullable','string','max:255'],
			
            'project_status_id' => ['required','integer','exists:st_project_statuses,id'],
            'priority_id' => ['required','integer','exists:lt_priorities,id'],
			
            'progress' => ['nullable','integer','min:0','max:100'],
            'start_date' => ['nullable','date'],
            'target_end_date' => ['nullable','date'],
            'actual_end_date' => ['nullable','date'],
			'project_category_id' => ['nullable','integer','exists:lt_project_categories,id'],
			'planned_progress'    => ['nullable','integer','min:0','max:100'],
			'actual_start_date'   => ['nullable','date'],
			'notes'               => ['nullable','string'],
			
			'currency_code' => ['nullable','string','size:3'],
			'planned_cost_total' => ['nullable','numeric','min:0'],
			'actual_cost_total' => ['nullable','numeric','min:0'],
			'committed_cost_total' => ['nullable','numeric','min:0'],
			'planned_funding_total' => ['nullable','numeric','min:0'],
			'actual_funding_total' => ['nullable','numeric','min:0'],
			'budget_notes' => ['nullable','string'],
			'budget_updated_at' => ['nullable','date'],
			];
		}
	}
