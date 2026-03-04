<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
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
        $roleParam = $this->route('role');              // Role model OR id
        $roleId = $roleParam instanceof Role
            ? $roleParam->getKey()
            : (int) $roleParam;

        return [
            'code' => [
                'sometimes', 'string', 'max:50',
                Rule::unique('lt_roles', 'code')->ignore($roleId),
            ],
            'name' => ['sometimes', 'string', 'max:150'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
