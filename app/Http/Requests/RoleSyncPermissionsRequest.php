<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Validation\Rule;
	
	class RoleSyncPermissionsRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			if (!$this->has('permission_ids') || $this->permission_ids === null) {
				$this->merge([
                'permission_ids' => [],
				]);
			}
		}
		
		public function rules(): array
		{
			return [
            'permission_ids' => ['required', 'array'],
			
            'permission_ids.*' => [
			'integer',
			'distinct',
			Rule::exists('lt_permissions', 'id')
			->where('is_active', true),
            ],
			];
		}
	}	