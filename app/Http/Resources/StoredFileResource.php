<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StoredFileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int)$this->id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => (int)($this->size ?? 0),
            'checksum' => $this->checksum,

            'disk' => $this->disk,
            'path' => $this->path,

            'uploaded_by_user_id' => $this->uploaded_by_user_id ? (int)$this->uploaded_by_user_id : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}