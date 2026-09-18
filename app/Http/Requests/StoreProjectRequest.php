<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\BelongsToSite;
use App\Rules\UserHasSiteAccess;

class StoreProjectRequest extends FormRequest
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
        $siteId = (int) $this->input('site_id');

        return [
            'site_id' => [
                'required',
                'integer',
                Rule::exists('lt_sites', 'id')
                    ->where(
                        fn($q) => $q->where('is_active', true)
                    ),
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('dt_projects', 'code')
                    ->where(
                        fn($q) => $q->where('site_id', $siteId)
                    ),
            ],

            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],

            'department_id' => [
                'nullable',
                'integer',
                'exists:lt_departments,id',
                new BelongsToSite(
                    table: 'lt_departments',
                    siteId: $siteId,
                    requireActive: true
                ),
            ],

            'project_category_id' => [
                'nullable',
                'integer',
                'exists:lt_project_categories,id',
            ],

            'owner_user_id' => [
                'nullable',
                'integer',
                'exists:users,id',
                new UserHasSiteAccess($siteId),
            ],

            'sponsor' => ['nullable', 'string', 'max:255'],

            'currency_code' => [
                'nullable',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
            ],

            'planned_cost_total' => ['nullable', 'numeric', 'min:0'],
            'actual_cost_total' => ['nullable', 'numeric', 'min:0'],
            'committed_cost_total' => ['nullable', 'numeric', 'min:0'],
            'planned_funding_total' => ['nullable', 'numeric', 'min:0'],
            'actual_funding_total' => ['nullable', 'numeric', 'min:0'],

            'budget_notes' => ['nullable', 'string'],
            'budget_updated_at' => ['nullable', 'date'],

            'project_status_id' => [
                'required',
                'integer',
                'exists:st_project_statuses,id',
            ],

            'priority_id' => [
                'required',
                'integer',
                'exists:lt_priorities,id',
            ],

            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'planned_progress' => ['nullable', 'integer', 'min:0', 'max:100'],

            'start_date' => ['nullable', 'date'],
            'actual_start_date' => ['nullable', 'date'],

            'target_end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'actual_end_date' => [
                'nullable',
                'date',
                'after_or_equal:actual_start_date',
            ],
        ];
    }
}
