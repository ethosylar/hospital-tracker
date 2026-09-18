<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'site_id' => $this->site_id !== null ? (int) $this->site_id : null,
            'site' => $this->whenLoaded(
                'site',
                fn() => $this->site
                    ? [
                        'id' => (int) $this->site->id,
                        'code' => $this->site->code,
                        'name' => $this->site->name,
                        'short_name' => $this->site->short_name,
                    ] : null
            ),
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id !== null ? (int) $this->entity_id : null,
            'action' => $this->action,
            'changes' => $this->changes,
            'performed_by_user_id' => $this->performed_by_user_id !== null ? (int) $this->performed_by_user_id : null,
            'user' =>
            $this->whenLoaded(
                'user',
                fn() => $this->user
                    ? [
                        'id' => (int) $this->user->id,
                        'name' => $this->user->name,
                        'email' => $this->user->email,
                    ] : null
            ),
            'source' => $this->source,
            'performed_at' => $this->performed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
