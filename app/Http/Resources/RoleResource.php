<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class RoleResource extends JsonResource
	{
		public function toArray($request): array
		{
			return [
            'id' => $this->id,
			'role_id' => (int)$this->id,
            'code' => $this->code,
            'name' => $this->name,
            'is_active' => (bool)$this->is_active,
			'is_system_role' => (bool) $this->is_system_role,
			'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
			];
		}
	}
