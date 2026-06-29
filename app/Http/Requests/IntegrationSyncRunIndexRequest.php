<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Validation\Rule;
	
	class IntegrationSyncRunIndexRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			if ($this->filled('status')) {
				$this->merge([
                'status' => strtoupper(trim((string) $this->status)),
				]);
			}
			
			if ($this->filled('sync_type')) {
				$this->merge([
                'sync_type' => strtoupper(trim((string) $this->sync_type)),
				]);
			}
		}
		
		public function rules(): array
		{
			return [
            'status' => [
			'nullable',
			Rule::in([
			'RUNNING',
			'COMPLETED',
			'PARTIAL',
			'FAILED',
			]),
            ],
			
            'sync_type' => [
			'nullable',
			Rule::in([
			'FULL',
			'INCREMENTAL',
			'MANUAL',
			]),
            ],
			
            'date_from' => ['nullable', 'date'],
            'date_to' => [
			'nullable',
			'date',
			'after_or_equal:date_from',
            ],
			
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
			];
		}
	}	