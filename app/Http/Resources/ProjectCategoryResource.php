<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class ProjectCategoryResource extends JsonResource
	{
		public function toArray($request): array
		{
			return [
            'id' => (int)$this->id,
            'code' => $this->code,
            'name' => $this->name,
            'group' => $this->group,
            'year' => $this->year,
            'sort_order' => (int)($this->sort_order ?? 0),
            'is_active' => (bool)$this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
			];
		}
	}	