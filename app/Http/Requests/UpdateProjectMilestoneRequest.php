<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => trim((string)$this->name)]);
        }
        if ($this->has('status') && $this->status !== null) {
            $this->merge(['status' => strtoupper(trim((string)$this->status))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'milestone_date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'nullable', 'string', 'max:30', Rule::in(['PENDING','DONE','CANCELLED'])],
        ];
    }
}
