<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Validation\Rule;
	
	class UpdateSeverityRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			$merge = [];
			
			if ($this->has('code')) $merge['code'] = strtoupper(trim((string)$this->input('code')));
			if ($this->has('name')) $merge['name'] = trim((string)$this->input('name'));
			
			if (!empty($merge)) $this->merge($merge);
		}
		
		public function rules(): array
		{
			$id = $this->route('severity'); // matches {severity}
			
			return [
            'code' => [
			'sometimes','string','max:50',
			Rule::unique('st_severities', 'code')->ignore($id),
            ],
            'name' => ['sometimes','string','max:150'],
            'sort_order' => ['sometimes','nullable','integer','min:0'],
            'is_active' => ['sometimes','nullable','boolean'],
			];
		}
	}
