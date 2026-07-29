<?php
	
	namespace App\Http\Requests;
	
	use App\Models\Counterparty;
	use Illuminate\Foundation\Http\FormRequest;
	use Illuminate\Validation\Rule;
	
	class UpdateCounterpartyRequest extends FormRequest
	{
		public function authorize(): bool
		{
			return true;
		}
		
		protected function prepareForValidation(): void
		{
			$this->normaliseInput();
		}
		
		public function rules(): array
		{
			$counterparty = $this->route('counterparty');
			$counterpartyId = is_object($counterparty)
            ? $counterparty->id
            : $counterparty;
			
			return [
            'code' => [
			'sometimes',
			'required',
			'string',
			'max:50',
			'regex:/^[A-Z0-9][A-Z0-9_-]*$/',
			Rule::unique('dt_counterparties', 'code')
			->ignore($counterpartyId),
            ],
            'counterparty_type' => [
			'sometimes',
			'required',
			'string',
			Rule::in([
			'COMPANY',
			'VENDOR',
			'GOVERNMENT_AGENCY',
			'INDIVIDUAL',
			'OTHER',
			]),
            ],
            'legal_name' => ['sometimes', 'required', 'string', 'max:255'],
            'trading_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'registration_no' => [
			'sometimes',
			'nullable',
			'string',
			'max:100',
			Rule::unique('dt_counterparties', 'registration_no')
			->ignore($counterpartyId),
            ],
            'tax_no' => ['sometimes', 'nullable', 'string', 'max:100'],
            'vendor_no' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contact_person' => ['sometimes', 'nullable', 'string', 'max:255'],
            'contact_position' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email:rfc', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'alternate_phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'address_line_1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line_2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:120'],
            'state' => ['sometimes', 'nullable', 'string', 'max:120'],
            'postcode' => ['sometimes', 'nullable', 'string', 'max:30'],
            'country' => ['sometimes', 'nullable', 'string', 'max:120'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
			];
		}
		
		private function normaliseInput(): void
		{
			$data = [];
			
			if ($this->has('code') && !blank($this->code)) {
				$data['code'] = strtoupper(trim((string) $this->code));
			}
			
			if ($this->has('counterparty_type')) {
				$data['counterparty_type'] = strtoupper(
                trim((string) $this->counterparty_type)
				);
			}
			
			foreach ([
            'legal_name',
            'trading_name',
            'tax_no',
            'vendor_no',
            'contact_person',
            'contact_position',
            'email',
            'phone',
            'alternate_phone',
            'address_line_1',
            'address_line_2',
            'city',
            'state',
            'postcode',
            'country',
            'notes',
			] as $field) {
				if (!$this->has($field)) {
					continue;
				}
				
				if ($this->input($field) === null) {
					$data[$field] = null;
					continue;
				}
				
				$value = trim((string) $this->input($field));
				$data[$field] = $value === '' ? null : $value;
			}
			
			if ($this->has('registration_no')) {
				$data['registration_no'] = Counterparty::normalizeRegistrationNo(
                $this->input('registration_no')
				);
			}
			
			if (!empty($data)) {
				$this->merge($data);
			}
		}
	}	