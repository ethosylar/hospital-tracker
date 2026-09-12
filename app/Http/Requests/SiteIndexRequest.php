<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiteIndexRequest extends FormRequest
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

        if ($this->filled('site_type')) {
            $this->merge([
                'site_type' => strtoupper(
                    trim((string) $this->site_type)
                ),
            ]);
        }

        if ($this->has('is_active')) {
            $value = filter_var(
                $this->input('is_active'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($value !== null) {
                $this->merge(['is_active' => $value]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],

            'site_type' => [
                'nullable',
                'string',
                Rule::in(['HQ', 'HOSPITAL', 'OTHER']),
            ],

            'is_active' => ['nullable', 'boolean'],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}
