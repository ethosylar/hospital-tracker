<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExternalRiskIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('external_id')) $this->merge(['external_id' => trim((string)$this->external_id)]);
        if ($this->has('title')) $this->merge(['title' => trim((string)$this->title)]);
        if ($this->has('owner') && $this->owner !== null) $this->merge(['owner' => trim((string)$this->owner)]);
    }

    public function rules(): array
    {
        return [
            'external_source_id' => ['sometimes','nullable','integer','exists:lt_external_sources,id'],
            'external_id' => ['sometimes','string','max:120'],

            'project_id' => ['sometimes','nullable','integer','exists:dt_projects,id'],
            'type_id' => ['sometimes','integer','exists:lt_risk_issue_types,id'],

            'title' => ['sometimes','string','max:255'],
            'description' => ['sometimes','nullable','string'],

            'severity_id' => ['sometimes','integer','exists:st_severities,id'],
            'risk_issue_status_id' => ['sometimes','integer','exists:st_risk_issue_statuses,id'],

            'owner' => ['sometimes','nullable','string','max:255'],
            'source_created_at' => ['sometimes','nullable','date'],
            'source_updated_at' => ['sometimes','nullable','date'],
            'last_synced_at' => ['sometimes','nullable','date'],

            'raw_payload' => ['sometimes','nullable'],
        ];
    }
}
