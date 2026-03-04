<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectMilestoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Put role/policy here if you want.
        // If your route middleware already controls access, keep true.
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
            'name' => ['required', 'string', 'max:255'],
            'milestone_date' => ['required', 'date'],
            'status' => ['nullable', 'string', 'max:30', Rule::in(['PENDING','DONE','CANCELLED'])],
        ];
    }
}
