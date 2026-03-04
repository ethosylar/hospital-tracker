<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExternalRiskIssueResource extends JsonResource
{
    public function toArray($request): array
    {
        $includePayload =
            $request->boolean('include_payload') ||
            $request->route('issue') !== null; // show route param name example

        $raw = $this->raw_payload;

        $decodedPayload = null;
        if ($includePayload && is_string($raw) && $raw !== '') {
            $try = json_decode($raw, true);
            $decodedPayload = (json_last_error() === JSON_ERROR_NONE) ? $try : $raw;
        } elseif ($includePayload && $raw === null) {
            $decodedPayload = null;
        }

        return [
            'id' => $this->id,

            'external_source_id' => $this->external_source_id,
            'external_source_code' => optional($this->externalSource)->code,
            'external_source_name' => optional($this->externalSource)->name,

            'external_id' => $this->external_id,

            'project_id' => $this->project_id,
            'project_code' => optional($this->project)->code,
            'project_name' => optional($this->project)->name,

            'type_id' => $this->type_id,
            'type_code' => optional($this->type)->code,
            'type_name' => optional($this->type)->name,

            'title' => $this->title,
            'description' => $this->description,

            'severity_id' => $this->severity_id,
            'severity_code' => optional($this->severity)->code,
            'severity_name' => optional($this->severity)->name,

            'risk_issue_status_id' => $this->risk_issue_status_id,
            'risk_issue_status_code' => optional($this->status)->code,
            'risk_issue_status_name' => optional($this->status)->name,

            'owner' => $this->owner,

            'source_created_at' => $this->source_created_at,
            'source_updated_at' => $this->source_updated_at,
            'last_synced_at' => $this->last_synced_at,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // only when show/include_payload
            'raw_payload' => $this->when($includePayload, $decodedPayload),
        ];
    }
}
