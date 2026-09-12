<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $value = filter_var(
                $this->input('is_active'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($value !== null) {
                $this->merge([
                    'is_active' => $value,
                ]);
            }
        }

        if ($this->filled('search')) {
            $this->merge([
                'search' => trim((string) $this->search),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'site_id' => [
                'nullable',
                'integer',
                Rule::exists('lt_sites', 'id'),
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}
