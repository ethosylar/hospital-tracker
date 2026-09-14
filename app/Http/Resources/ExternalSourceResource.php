<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExternalSourceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'site_id' => (int) $this->site_id,
            'code' => $this->code,
            'name' => $this->name,
            'base_url' => $this->base_url,
            'is_active' => (bool) $this->is_active,
            'site' => $this->whenLoaded(
                'site',
                fn() => [
                    'id' => (int) $this->site->id,
                    'code' => $this->site->code,
                    'name' => $this->site->name,
                    'short_name' => $this->site->short_name,
                ]
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
