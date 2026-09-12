<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(
                    trim((string) $this->code)
                ),
            ]);
        }

        if ($this->has('name')) {
            $this->merge([
                'name' => trim((string) $this->name),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'site_id' => [
                'required',
                'integer',
                Rule::exists('lt_sites', 'id')
                    ->where(
                        fn($query) => $query->where(
                            'is_active',
                            true
                        )
                    ),
            ],

            'code' => [
                'required',
                'string',
                'max:50',
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }
}
