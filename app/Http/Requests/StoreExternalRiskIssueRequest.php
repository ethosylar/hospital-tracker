<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExternalRiskIssueRequest extends FormRequest
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
            'external_source_id' => ['nullable','integer','exists:lt_external_sources,id'],
            'external_id' => ['required','string','max:120'],

            'project_id' => ['nullable','integer','exists:dt_projects,id'],
            'type_id' => ['required','integer','exists:lt_risk_issue_types,id'],

            'title' => ['required','string','max:255'],
            'description' => ['nullable','string'],

            'severity_id' => ['required','integer','exists:st_severities,id'],
            'risk_issue_status_id' => ['required','integer','exists:st_risk_issue_statuses,id'],

            'owner' => ['nullable','string','max:255'],
            'source_created_at' => ['nullable','date'],
            'source_updated_at' => ['nullable','date'],
            'last_synced_at' => ['nullable','date'],

            // we validate/normalize in controller (can be array|string|null)
            'raw_payload' => ['nullable'],
        ];
    }
}
