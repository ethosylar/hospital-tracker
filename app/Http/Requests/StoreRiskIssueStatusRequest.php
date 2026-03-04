<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRiskIssueStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // your route middleware (role:ADMIN) controls access
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string)$this->code))]);
        }
        if ($this->has('name')) {
            $this->merge(['name' => trim((string)$this->name)]);
        }
        if ($this->has('sort_order') && $this->sort_order === '') {
            $this->merge(['sort_order' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required','string','max:50','unique:st_risk_issue_statuses,code'],
            'name' => ['required','string','max:150'],
            'sort_order' => ['nullable','integer','min:0'],
            'is_active' => ['nullable','boolean'],
        ];
    }
}
