<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // keep true because access control is already enforced by route middleware (role:ADMIN)
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required','string','max:50','unique:lt_departments,code'],
            'name' => ['required','string','max:150'],
            'is_active' => ['nullable','boolean'],
        ];
    }
}
