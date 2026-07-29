<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class AgreementStatusResource extends JsonResource
	{
		public function toArray($request): array
		{
			return [
            'id' => (int) $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'sort_order' => (int) $this->sort_order,
            'is_terminal' => (bool) $this->is_terminal,
            'is_system_status' => (bool) $this->is_system_status,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
			];
		}
	}
