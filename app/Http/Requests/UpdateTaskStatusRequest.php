<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
        $status = $this->route('status'); // model OR id

        return [
            'code' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('st_task_statuses', 'code')->ignore($status),
            ],
            'name' => ['sometimes','string','max:150'],
            'sort_order' => ['sometimes','nullable','integer','min:0'],
            'is_active' => ['sometimes','nullable','boolean'],
        ];
    }
}
