<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray($request): array
    {
        // changes may be TEXT JSON or JSON column.
        $changes = $this->changes;

        if (is_string($changes) && $changes !== '') {
            $decoded = json_decode($changes, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $changes = $decoded;
            }
        }

        return [
            'id' => (int) $this->id,

            'entity_type' => $this->entity_type,
            'entity_id'   => $this->entity_id !== null ? (int) $this->entity_id : null,
            'action'      => $this->action,
            'source'      => $this->source,

            'performed_at' => $this->performed_at,
            'created_at'   => $this->created_at,

            'changes' => $changes,

            'user' => $this->when(
                $this->relationLoaded('user') && $this->user,
                fn () => [
                    'id'    => (int) $this->user->id,
                    'name'  => $this->user->name,
                    'email' => $this->user->email,
                ]
            ),
        ];
    }
}
