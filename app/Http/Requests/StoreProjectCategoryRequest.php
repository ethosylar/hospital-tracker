<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class StoreProjectCategoryRequest extends FormRequest
	{
		public function authorize(): bool { return true; }
		
		protected function prepareForValidation(): void
		{
			if ($this->has('code')) $this->merge(['code' => strtoupper(trim((string)$this->code))]);
			if ($this->has('name')) $this->merge(['name' => trim((string)$this->name)]);
			if ($this->has('group') && $this->group !== null) $this->merge(['group' => strtoupper(trim((string)$this->group))]);
		}
		
		public function rules(): array
		{
			return [
            'code' => ['required','string','max:50','unique:lt_project_categories,code'],
            'name' => ['required','string','max:150'],
            'group' => ['nullable','string','max:20'],
            'year' => ['nullable','integer','min:2000','max:2100'],
            'sort_order' => ['nullable','integer','min:0'],
            'is_active' => ['nullable','boolean'],
			];
		}
	}	