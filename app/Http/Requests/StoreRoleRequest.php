<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class StoreRoleRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true; // route middleware handles role access
		}
		
		protected function prepareForValidation(): void
		{
			if ($this->has('code')) {
				$this->merge(['code' => strtoupper(trim((string)$this->code))]);
			}
			if ($this->has('name')) {
				$this->merge(['name' => trim((string)$this->name)]);
			}
			if (!$this->has('permission_ids')) {
				$this->merge([
                'permission_ids' => [],
				]);
			}
		}
		
		public function rules(): array
		{
			return [
            'code' => ['required','string','max:50','unique:lt_roles,code'],
            'name' => ['required','string','max:150'],
            'is_active' => ['nullable','boolean'],
			'permission_ids' => ['nullable', 'array'],
			
            'permission_ids.*' => [
			'integer',
			'distinct',
			Rule::exists('lt_permissions', 'id')->where('is_active', true),
            ],
			];
		}
	}
