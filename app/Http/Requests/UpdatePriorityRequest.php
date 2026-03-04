<?php

namespace App\Http\Requests;

use App\Models\Priority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
        if ($this->has('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }
    }

    public function rules(): array
    {
        $priorityParam = $this->route('priority'); // Priority model OR id
        $priorityId = $priorityParam instanceof Priority
            ? $priorityParam->getKey()
            : (int) $priorityParam;

        return [
            'code' => [
                'sometimes', 'string', 'max:50',
                Rule::unique('lt_priorities', 'code')->ignore($priorityId),
            ],
            'name' => ['sometimes', 'string', 'max:150'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
