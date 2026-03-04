<?php
	
	namespace App\Http\Requests\Api;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class UserStoreRequest extends FormRequest
	{
		public function authorize(): bool { return true; }
		
		public function rules(): array
		{
			return [
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
			'username' => ['nullable','string','max:100','unique:users,username'],
			'department_id' => ['nullable','integer','exists:lt_departments,id'],
            'password' => ['required','string','min:8'],
            'role_ids' => ['nullable','array'],
            'role_ids.*' => ['integer','exists:lt_roles,id'],
			];
		}
	}
