<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int)$this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'sponsor' => $this->sponsor,

            'progress' => (int)($this->progress ?? 0),
            'start_date' => $this->start_date,
            'target_end_date' => $this->target_end_date,
            'actual_end_date' => $this->actual_end_date,

            'department' => $this->whenLoaded('department', function () {
                return [
                    'id' => (int)$this->department->id,
                    'code' => $this->department->code,
                    'name' => $this->department->name,
                ];
            }),

            'status' => $this->whenLoaded('status', function () {
                return [
                    'id' => (int)$this->status->id,
                    'code' => $this->status->code,
                    'name' => $this->status->name,
                ];
            }),

            'priority' => $this->whenLoaded('priority', function () {
                return [
                    'id' => (int)$this->priority->id,
                    'code' => $this->priority->code,
                    'name' => $this->priority->name,
                ];
            }),

            'owner' => $this->whenLoaded('owner', function () {
                return $this->owner ? [
                    'id' => (int)$this->owner->id,
                    'name' => $this->owner->name,
                    'email' => $this->owner->email,
                ] : null;
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
