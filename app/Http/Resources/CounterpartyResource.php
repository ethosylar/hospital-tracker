<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class CounterpartyResource extends JsonResource
	{
		public function toArray($request): array
		{
			return [
            'id' => (int) $this->id,
            'code' => $this->code,
            'counterparty_type' => $this->counterparty_type,
			
            'legal_name' => $this->legal_name,
            'trading_name' => $this->trading_name,
			
            'registration_no' => $this->registration_no,
            'tax_no' => $this->tax_no,
            'vendor_no' => $this->vendor_no,
			
            'contact_person' => $this->contact_person,
            'contact_position' => $this->contact_position,
            'email' => $this->email,
            'phone' => $this->phone,
            'alternate_phone' => $this->alternate_phone,
			
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'postcode' => $this->postcode,
            'country' => $this->country,
			
            'notes' => $this->notes,
            'is_active' => (bool) $this->is_active,
			
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
			];
		}
	}	