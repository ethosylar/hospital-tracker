<?php
	
	namespace App\Http\Resources;
	
	use Illuminate\Http\Resources\Json\JsonResource;
	
	class UserResource extends JsonResource
	{
		public function toArray($request): array
		{
			return [
            'id' => (int)$this->id,
            'name' => $this->name,
			'username' => $this->username,
            'email' => $this->email,
			'department' => $this->whenLoaded('department', fn () => [
            'id' => $this->department->id,
            'code' => $this->department->code,
            'name' => $this->department->name,
			]),
			'roles' => RoleResource::collection(
			$this->whenLoaded('roles')
			),
			'permissions' => $this->whenLoaded('roles', function () {
				return $this->roles
				->flatMap(function ($role) {
					return $role->relationLoaded('permissions') ? $role->permissions : collect();
				})
				->where('is_active', true)
				->pluck('code')
				->unique()
				->values();
			}),
            'created_at' => optional($this->created_at)?->toISOString(),
            'updated_at' => optional($this->updated_at)?->toISOString(),
			];
		}
	}
