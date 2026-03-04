<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // protected by route middleware role:ADMIN already
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required','string','max:50','unique:st_project_statuses,code'],
            'name' => ['required','string','max:150'],
            'sort_order' => ['nullable','integer','min:0'],
            'is_active' => ['nullable','boolean'],
        ];
    }
}
