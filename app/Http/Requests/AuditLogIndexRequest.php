<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuditLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Put your policy/guard here if needed.
        // For now follow your other controllers pattern: allow.
        return true;
    }

    public function rules(): array
    {
        return [
            'entity_type' => ['nullable', 'string', 'max:80'],
            'entity_id'   => ['nullable', 'integer', 'min:1'],
            'action'      => ['nullable', 'string', 'max:50'],
            'user_id'     => ['nullable', 'integer', 'min:1'],

            'from'        => ['nullable', 'date_format:Y-m-d'],
            'to'          => ['nullable', 'date_format:Y-m-d'],

            'search'      => ['nullable', 'string', 'max:255'],

            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('search')) {
            $this->merge(['search' => trim((string) $this->input('search'))]);
        }
        if ($this->has('entity_type')) {
            $this->merge(['entity_type' => trim((string) $this->input('entity_type'))]);
        }
        if ($this->has('action')) {
            $this->merge(['action' => trim((string) $this->input('action'))]);
        }
    }
}
