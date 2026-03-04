<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExternalSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) $this->merge(['code' => strtoupper(trim((string) $this->code))]);
        if ($this->has('name')) $this->merge(['name' => trim((string) $this->name)]);
        if ($this->has('base_url')) {
            $v = $this->base_url;
            $this->merge(['base_url' => $v === null ? null : trim((string) $v)]);
        }
    }

    public function rules(): array
    {
        $source = $this->route('source'); // model OR id (ignore() handles both)

        return [
            'code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('lt_external_sources', 'code')->ignore($source),
            ],
            'name' => ['sometimes','string','max:150'],
            'base_url' => ['sometimes','nullable','string','max:255'],
            'is_active' => ['sometimes','nullable','boolean'],
        ];
    }
}
