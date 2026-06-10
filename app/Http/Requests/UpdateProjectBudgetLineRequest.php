<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class UpdateProjectBudgetLineRequest extends FormRequest
	{
		public function authorize(): bool { return true; }
		
		protected function prepareForValidation(): void
		{
			if ($this->has('line_type')) $this->merge(['line_type' => strtoupper(trim((string)$this->line_type))]);
			if ($this->has('code')) $this->merge(['code' => strtoupper(trim((string)$this->code))]);
			if ($this->has('name')) $this->merge(['name' => trim((string)$this->name)]);
		}
		
		public function rules(): array
		{
			return [
            'line_type' => ['sometimes','string','in:COST,FUNDING'],
            'code' => ['sometimes','string','max:50'],
            'name' => ['sometimes','string','max:150'],
            'planned_amount' => ['sometimes','nullable','numeric','min:0'],
            'actual_amount' => ['sometimes','nullable','numeric','min:0'],
            'committed_amount' => ['sometimes','nullable','numeric','min:0'],
            'sort_order' => ['sometimes','nullable','integer','min:0'],
            'is_active' => ['sometimes','nullable','boolean'],
            'notes' => ['sometimes','nullable','string'],
			];
		}
	}	