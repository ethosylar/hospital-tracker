<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware handles roles
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string)$this->code))]);
        }
        if ($this->has('name')) {
            $this->merge(['name' => trim((string)$this->name)]);
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required','string','max:50','unique:lt_priorities,code'],
            'name' => ['required','string','max:150'],
            'sort_order' => ['nullable','integer','min:0'],
            'is_active' => ['nullable','boolean'],
        ];
    }
}
