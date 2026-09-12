<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserSiteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'user_id' => (int) $this->user_id,
            'site_id' => (int) $this->site_id,
            'access_level' => $this->access_level,
            'is_primary' => (bool) $this->is_primary,
            'is_active' => (bool) $this->is_active,

            'site' => $this->whenLoaded('site', function () {
                return [
                    'id' => (int) $this->site->id,
                    'code' => $this->site->code,
                    'name' => $this->site->name,
                    'short_name' => $this->site->short_name,
                    'site_type' => $this->site->site_type,
                    'is_active' => (bool) $this->site->is_active,
                ];
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
