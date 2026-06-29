<?php
	
	namespace App\Http\Requests;
	
	use Illuminate\Foundation\Http\FormRequest;
	
	class ImportEptwPermitsRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		public function rules(): array
		{
			return [
            'sync_type' => [
			'nullable',
			'string',
			'in:FULL,INCREMENTAL,MANUAL',
            ],
			
            'cursor_from' => ['nullable', 'string', 'max:255'],
            'cursor_to' => ['nullable', 'string', 'max:255'],
			
            'permits' => [
			'required',
			'array',
			'min:1',
			'max:500',
            ],
			
            'permits.*.external_form_id' => [
			'required',
			'string',
			'max:50',
            ],
			
            'permits.*.external_permit_id' => [
			'nullable',
			'string',
			'max:50',
            ],
			
            'permits.*.status' => [
			'nullable',
			'string',
			'max:50',
            ],
			
            'permits.*.applicant_name' => [
			'nullable',
			'string',
			'max:150',
            ],
			
            'permits.*.service_name' => [
			'nullable',
			'string',
			'max:255',
            ],
			
            'permits.*.company_name' => [
			'nullable',
			'string',
			'max:500',
            ],
			
            'permits.*.supervisor_name' => [
			'nullable',
			'string',
			'max:150',
            ],
			
            'permits.*.exact_location' => [
			'nullable',
			'string',
			'max:255',
            ],
			
            'permits.*.work_type' => ['nullable', 'string'],
            'permits.*.hazards' => ['nullable', 'string'],
            'permits.*.ppe' => ['nullable', 'string'],
            'permits.*.worksite_controls' => ['nullable', 'string'],
            'permits.*.infection_controls' => ['nullable', 'string'],
            'permits.*.remark' => ['nullable', 'string'],
			
            'permits.*.work_start_date' => ['nullable', 'date'],
            'permits.*.work_end_date' => [
			'nullable',
			'date',
			'after_or_equal:permits.*.work_start_date',
            ],
			
            /*
				* Kept as strings because the legacy system may return
				* either HH:mm or HH:mm:ss.
			*/
            'permits.*.work_start_time' => [
			'nullable',
			'string',
			'max:20',
            ],
			
            'permits.*.work_end_time' => [
			'nullable',
			'string',
			'max:20',
            ],
			
            'permits.*.brief_date' => ['nullable', 'date'],
            'permits.*.brief_time' => [
			'nullable',
			'string',
			'max:20',
            ],
			
            'permits.*.brief_conducted_by' => [
			'nullable',
			'string',
			'max:150',
            ],
			
            'permits.*.source_created_at' => ['nullable', 'date'],
            'permits.*.source_updated_at' => ['nullable', 'date'],
			
            /*
				* Internal hostnames may not pass Laravel's URL validation,
				* therefore this remains a bounded string.
			*/
            'permits.*.source_url' => [
			'nullable',
			'string',
			'max:1000',
            ],
			
            'permits.*.is_deleted' => ['nullable', 'boolean'],
			];
		}
	}	