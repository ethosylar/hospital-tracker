<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Resources\Json\JsonResource;

class ChartItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'value' => (int)($this->value ?? 0),
        ];
    }
}
