<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Resources\Json\JsonResource;

class MilestoneMiniResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int)$this->id,
            'name' => $this->name,
            'milestone_date' => $this->milestone_date,
            'project' => [
                'id' => (int)($this->project->id ?? 0),
                'code' => $this->project->code ?? null,
                'name' => $this->project->name ?? null,
            ],
        ];
    }
}
