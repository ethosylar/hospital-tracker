<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UserSyncRolesRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'role_ids' => ['required','array'],
            'role_ids.*' => ['integer','exists:lt_roles,id'],
        ];
    }
}
