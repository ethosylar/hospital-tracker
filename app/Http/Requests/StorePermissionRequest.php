<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Validation\Rule;
	
	class StorePermissionRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			if ($this->has('code')) {
				$this->merge([
                'code' => strtolower(trim((string) $this->code)),
				]);
			}
			
			if ($this->has('name')) {
				$this->merge([
                'name' => trim((string) $this->name),
				]);
			}
			
			if ($this->has('module')) {
				$this->merge([
                'module' => trim((string) $this->module),
				]);
			}
		}
		
		public function rules(): array
		{
			return [
            'code' => [
			'required',
			'string',
			'max:100',
			'regex:/^[a-z0-9._-]+$/',
			Rule::unique('lt_permissions', 'code'),
            ],
			
            'name' => ['required', 'string', 'max:150'],
            'module' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
			];
		}
		
		public function messages(): array
		{
			return [
            'code.regex' => 'Permission code may only contain lowercase letters, numbers, dot, dash and underscore. Example: projects.write',
			];
		}
	}	