<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('code')) {
            $data['code'] = strtoupper(
                trim((string) $this->code)
            );
        }

        if ($this->has('site_type')) {
            $data['site_type'] = strtoupper(
                trim((string) $this->site_type)
            );
        }

        foreach ([
            'name',
            'short_name',
            'address_line_1',
            'address_line_2',
            'city',
            'state',
            'postcode',
            'country',
        ] as $field) {
            if (!$this->has($field)) {
                continue;
            }

            if ($this->input($field) === null) {
                $data[$field] = null;
                continue;
            }

            $value = trim((string) $this->input($field));
            $data[$field] = $value === '' ? null : $value;
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9][A-Z0-9_-]*$/',
            ],

            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'short_name' => [
                'sometimes',
                'nullable',
                'string',
                'max:150',
            ],

            'site_type' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['HQ', 'HOSPITAL', 'OTHER']),
            ],

            'address_line_1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line_2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:120'],
            'state' => ['sometimes', 'nullable', 'string', 'max:120'],
            'postcode' => ['sometimes', 'nullable', 'string', 'max:30'],
            'country' => ['sometimes', 'nullable', 'string', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}