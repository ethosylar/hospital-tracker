<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ExternalRiskIssue;
use App\Rules\BelongsToSite;

class UpdateExternalRiskIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('external_id')) {
            $data['external_id'] = trim((string) $this->input('external_id'));
        }

        if ($this->has('title')) {
            $data['title'] = trim((string) $this->input('title'));
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
        $routeIssue = $this->route('issue');
        $issueId = is_object($routeIssue) ? (int) $routeIssue->id : (int) $routeIssue;
        $existing = is_object($routeIssue) ? $routeIssue : ExternalRiskIssue::query()->find($issueId);
        $candidateSiteId = (int) ($this->input('site_id') ?? $existing?->site_id ?? 0);

        return [
            'site_id' => ['sometimes', 'required', 'integer', Rule::exists('lt_sites', 'id')->where(fn($query) => $query->where('is_active', true)),],
            'external_source_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:lt_external_sources,id',

                new BelongsToSite(
                    table: 'lt_external_sources',

                    siteId: $candidateSiteId,

                    requireActive: true
                ),
            ],
            'external_id' => ['sometimes', 'required', 'string', 'max:120',],
            'project_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:dt_projects,id',

                new BelongsToSite(
                    table: 'dt_projects',

                    siteId: $candidateSiteId
                ),
            ],
            'type_id' => ['sometimes', 'required', 'integer', 'exists:lt_risk_issue_types,id',],
            'title' => ['sometimes', 'required', 'string', 'max:255',],
            'description' => ['sometimes', 'nullable', 'string',],
            'severity_id' => ['sometimes', 'required', 'integer', 'exists:st_severities,id',],
            'risk_issue_status_id' => ['sometimes', 'required', 'integer', 'exists:st_risk_issue_statuses,id',],
            'owner' => ['sometimes', 'nullable', 'string', 'max:255',],
            'source_created_at' => ['sometimes', 'nullable', 'date',],
            'source_updated_at' => ['sometimes', 'nullable', 'date',],
            'last_synced_at' => ['sometimes', 'nullable', 'date',],
            'raw_payload' => ['sometimes', 'nullable',],
        ];
    }
}
