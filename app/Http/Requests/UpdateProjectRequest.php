<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\BelongsToSite;
use App\Rules\UserHasSiteAccess;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim((string) $this->code)),
            ]);
        }

        if ($this->has('currency_code')) {
            $this->merge([
                'currency_code' => strtoupper(
                    trim((string) $this->currency_code)
                ),
            ]);
        }

        foreach (
            [
                'name',
                'description',
                'notes',
                'sponsor',
                'budget_notes',
            ] as $field
        ) {
            if (!$this->has($field)) {
                continue;
            }

            $value = $this->input($field);

            if ($value === null) {
                continue;
            }

            $value = trim((string) $value);

            $this->merge([
                $field => $value === '' ? null : $value,
            ]);
        }
    }

    public function rules(): array
    {
        $routeProject = $this->route('project');

        $projectId = is_object($routeProject)
            ? (int) $routeProject->id
            : (int) $routeProject;

        $existing = is_object($routeProject)
            ? $routeProject
            : Project::query()->find($projectId);

        $candidateSiteId = (int) (
            $this->input('site_id')
            ?? $existing?->site_id
            ?? 0
        );

        return [
            'site_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('lt_sites', 'id')
                    ->where(fn($q) => $q->where('is_active', true)),
            ],

            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('dt_projects', 'code')
                    ->where(
                        fn($q) => $q->where('site_id', $candidateSiteId)
                    )
                    ->ignore($projectId),
            ],

            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],

            'department_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:lt_departments,id',
                new BelongsToSite(
                    table: 'lt_departments',
                    siteId: $candidateSiteId,
                    requireActive: true
                ),
            ],

            'project_category_id' => ['sometimes', 'nullable', 'integer', 'exists:lt_project_categories,id',],
            'owner_user_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:users,id',
                new UserHasSiteAccess($candidateSiteId),
            ],
            'sponsor' => ['sometimes', 'nullable', 'string', 'max:255'],
            'currency_code' => ['sometimes', 'nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/',],
            'planned_cost_total' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'actual_cost_total' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'committed_cost_total' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'planned_funding_total' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'actual_funding_total' => ['sometimes', 'nullable', 'numeric', 'min:0'],

            'budget_notes' => ['sometimes', 'nullable', 'string'],
            'budget_updated_at' => ['sometimes', 'nullable', 'date'],

            'project_status_id' => ['sometimes', 'required', 'integer', 'exists:st_project_statuses,id',],

            'priority_id' => ['sometimes', 'required', 'integer', 'exists:lt_priorities,id',],

            'progress' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'planned_progress' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],

            'start_date' => ['sometimes', 'nullable', 'date'],
            'actual_start_date' => ['sometimes', 'nullable', 'date'],
            'target_end_date' => ['sometimes', 'nullable', 'date'],
            'actual_end_date' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
