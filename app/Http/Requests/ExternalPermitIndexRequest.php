<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Validation\Rule;
	
	class ExternalPermitIndexRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			if ($this->filled('normalized_status')) {
				$this->merge([
                'normalized_status' => strtoupper(
				trim((string) $this->normalized_status)
                ),
				]);
			}
			
			if ($this->filled('raw_status')) {
				$this->merge([
                'raw_status' => trim((string) $this->raw_status),
				]);
			}
		}
		
		public function rules(): array
		{
			return [
            'search' => ['nullable', 'string', 'max:255'],
			
            'normalized_status' => [
			'nullable',
			'string',
			Rule::in([
			'PENDING',
			'ACTIVE',
			'SUSPENDED',
			'COMPLETED',
			'CANCELLED',
			'UNKNOWN',
			]),
            ],
			
            'raw_status' => ['nullable', 'string', 'max:50'],
            'company_name' => ['nullable', 'string', 'max:500'],
            'service_name' => ['nullable', 'string', 'max:255'],
			
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
			
            'project_id' => [
			'nullable',
			'integer',
			'exists:dt_projects,id',
            ],
			
            'task_id' => [
			'nullable',
			'integer',
			'exists:dt_project_tasks,id',
            ],
			
            'is_linked' => ['nullable', 'boolean'],
            'include_deleted' => ['nullable', 'boolean'],
			
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
			];
		}
	}	