<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Validation\Rule;
	
	class UpdateRoleRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			if ($this->has('code')) {
				$this->merge([
                'code' => strtoupper(trim((string) $this->code)),
				]);
			}
			
			if ($this->has('name')) {
				$this->merge([
                'name' => trim((string) $this->name),
				]);
			}
		}
		
		public function rules(): array
		{
			$role = $this->route('role');
			$roleId = is_object($role) ? $role->getKey() : $role;
			
			return [
            'code' => [
			'sometimes',
			'string',
			'max:50',
			Rule::unique('lt_roles', 'code')->ignore($roleId),
            ],
			
            'name' => ['sometimes', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
			
            'permission_ids' => ['sometimes', 'array'],
			
            'permission_ids.*' => [
			'integer',
			'distinct',
			Rule::exists('lt_permissions', 'id')->where('is_active', true),
            ],
			];
		}
	}	