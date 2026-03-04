<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRiskIssueTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) $this->merge(['code' => strtoupper(trim((string) $this->code))]);
        if ($this->has('name')) $this->merge(['name' => trim((string) $this->name)]);
    }

    public function rules(): array
    {
        $type = $this->route('type'); // could be model OR id (both ok with ignore)

        return [
            'code' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('lt_risk_issue_types', 'code')->ignore($type),
            ],
            'name' => ['sometimes','string','max:50'],
            'is_active' => ['nullable','boolean'],
        ];
    }
}
