<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExternalSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // role middleware already controls access
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
        return [
            'code' => ['required','string','max:50','unique:lt_external_sources,code'],
            'name' => ['required','string','max:150'],
            // If you want strict URL validation, replace with: ['nullable','url','max:255']
            'base_url' => ['nullable','string','max:255'],
            'is_active' => ['nullable','boolean'],
        ];
    }
}
