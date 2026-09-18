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
            'site_id' => (int) $this->site_id,

            'site' => $this->whenLoaded(
                'site',
                fn() =>
                $this->site
                    ? [
                        'id' => (int) $this->site->id,
                        'code' => $this->site->code,
                        'name' => $this->site->name,
                        'short_name' => $this->site->short_name,
                    ] : null
            ),

            'uploaded_by_user_id' => $this->uploaded_by_user_id ? (int)$this->uploaded_by_user_id : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
