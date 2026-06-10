<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class StoreProjectBudgetLineRequest extends FormRequest
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
            'line_type' => ['nullable','string','in:COST,FUNDING'],
            'code' => ['required','string','max:50'],
            'name' => ['required','string','max:150'],
            'planned_amount' => ['nullable','numeric','min:0'],
            'actual_amount' => ['nullable','numeric','min:0'],
            'committed_amount' => ['nullable','numeric','min:0'],
            'sort_order' => ['nullable','integer','min:0'],
            'is_active' => ['nullable','boolean'],
            'notes' => ['nullable','string'],
			];
		}
	}	