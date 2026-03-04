<?php
	
	namespace App\Http\Requests\Api;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class UserUpdateRequest extends FormRequest
	{
		public function authorize(): bool { return true; }
		
		public function rules(): array
		{
			$userId = (int)$this->route('user');
			
			return [
            'name' => ['sometimes','string','max:255'],
			'username' => ['sometimes','nullable','string','max:100',"unique:users,username,{$this->route('user')}"],
			'department_id' => ['sometimes','nullable','integer','exists:lt_departments,id'],
            'email' => ['sometimes','email','max:255',"unique:users,email,{$userId}"],
            'password' => ['nullable','string','min:8'],
			];
		}
	}
