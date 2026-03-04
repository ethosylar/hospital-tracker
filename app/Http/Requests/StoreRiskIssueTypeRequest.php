<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiskIssueTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization should be handled by your route middleware (role:ADMIN etc)
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) $this->merge(['code' => strtoupper(trim((string) $this->code))]);
        if ($this->has('name')) $this->merge(['name' => trim((string) $this->name)]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required','string','max:20','unique:lt_risk_issue_types,code'],
            'name' => ['required','string','max:50'],
            'is_active' => ['nullable','boolean'],
        ];
    }
}
