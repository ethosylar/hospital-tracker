<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class AgreementCategoryResource extends JsonResource
	{
		public function toArray($request): array
		{
			return [
            'id' => (int) $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'sort_order' => (int) $this->sort_order,
            'is_system_category' => (bool) $this->is_system_category,
            'is_active' => (bool) $this->is_active,
            'types_count' => $this->whenCounted('types'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
			];
		}
	}	