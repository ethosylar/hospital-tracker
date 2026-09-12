<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProjectIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('search')) {
            $this->merge([
                'search' => trim((string) $this->search),
            ]);
        }

        if ($this->has('delayed')) {
            $value = filter_var(
                $this->input('delayed'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($value !== null) {
                $this->merge(['delayed' => $value]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'site_id' => ['nullable', 'integer', 'exists:lt_sites,id'],
            'department_id' => ['nullable', 'integer', 'exists:lt_departments,id'],
            'status_id' => ['nullable', 'integer', 'exists:st_project_statuses,id'],
            'priority_id' => ['nullable', 'integer', 'exists:lt_priorities,id'],
            'project_category_id' => ['nullable', 'integer', 'exists:lt_project_categories,id'],
            'owner_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'delayed' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
