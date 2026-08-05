<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class AgreementTypeResource extends JsonResource
	{
		public function toArray($request): array
		{
			return [
            'id' => (int) $this->id,
            'agreement_category_id' => $this->agreement_category_id
			? (int) $this->agreement_category_id
			: null,
			
            'category' => $this->whenLoaded('category', function () {
                return $this->category
				? [
				'id' => (int) $this->category->id,
				'code' => $this->category->code,
				'name' => $this->category->name,
				]
				: null;
			}),
			
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'sort_order' => (int) $this->sort_order,
            'is_system_type' => (bool) $this->is_system_type,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
			];
		}
	}	