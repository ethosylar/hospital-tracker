<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	use App\Models\ProjectCategory;
	
	class UpdateProjectCategoryRequest extends FormRequest
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
			$routeVal = $this->route('category');
			$id = $routeVal instanceof ProjectCategory ? $routeVal->id : (int)$routeVal;
			
			return [
            'code' => ['sometimes','string','max:50',"unique:lt_project_categories,code,{$id}"],
            'name' => ['sometimes','string','max:150'],
            'group' => ['nullable','string','max:20'],
            'year' => ['nullable','integer','min:2000','max:2100'],
            'sort_order' => ['nullable','integer','min:0'],
            'is_active' => ['nullable','boolean'],
			];
		}
	}	