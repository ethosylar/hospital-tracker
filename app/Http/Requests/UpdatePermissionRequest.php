<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Validation\Rule;
	
	class UpdatePermissionRequest extends FormRequest
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
			$permission = $this->route('permission');
			$permissionId = is_object($permission) ? $permission->getKey() : $permission;
			
			return [
            'code' => [
			'sometimes',
			'string',
			'max:100',
			'regex:/^[a-z0-9._-]+$/',
			Rule::unique('lt_permissions', 'code')->ignore($permissionId),
            ],
			
            'name' => ['sometimes', 'string', 'max:150'],
            'module' => ['sometimes', 'nullable', 'string', 'max:80'],
            'description' => ['sometimes', 'nullable', 'string'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
			];
		}
	}	