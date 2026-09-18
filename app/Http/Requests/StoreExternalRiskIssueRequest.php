<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\BelongsToSite;

class StoreExternalRiskIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('external_id')) {
            $data['external_id'] = trim(
                (string) $this->input(
                    'external_id'
                )
            );
        }

        if ($this->has('title')) {
            $data['title'] = trim(
                (string) $this->input(
                    'title'
                )
            );
        }

        if ($this->has('owner') && $this->input('owner') !== null) {
            $owner = trim((string) $this->input('owner'));

            $data['owner'] = $owner === '' ? null : $owner;
        }

        if ($data) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        $siteId = (int) $this->input('site_id');
        return [
            'site_id' => ['required', 'integer', Rule::exists('lt_sites', 'id')->where(fn($query) => $query->where('is_active', true)),],
            'external_source_id' => [
                'nullable',
                'integer',
                'exists:lt_external_sources,id',

                new BelongsToSite(
                    table: 'lt_external_sources',

                    siteId: $siteId,

                    requireActive: true
                ),
            ],
            'external_id' => ['required', 'string', 'max:120',],
            'project_id' => [
                'nullable',
                'integer',
                'exists:dt_projects,id',

                new BelongsToSite(
                    table: 'dt_projects',

                    siteId: $siteId
                ),
            ],
            'type_id' => ['required', 'integer', 'exists:lt_risk_issue_types,id',],
            'title' => ['required', 'string', 'max:255',],
            'description' => ['nullable', 'string',],
            'severity_id' => ['required', 'integer', 'exists:st_severities,id',],
            'risk_issue_status_id' => ['required', 'integer', 'exists:st_risk_issue_statuses,id',],
            'owner' => ['nullable', 'string', 'max:255',],
            'source_created_at' => ['nullable', 'date',],
            'source_updated_at' => ['nullable', 'date',],
            'last_synced_at' => ['nullable', 'date',],
            'raw_payload' => ['nullable',],
        ];
    }
}
