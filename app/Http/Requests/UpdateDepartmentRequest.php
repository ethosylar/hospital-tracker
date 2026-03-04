<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('department'); // route param name

        return [
            'code' => ['sometimes','string','max:50',"unique:lt_departments,code,{$id}"],
            'name' => ['sometimes','string','max:150'],
            'is_active' => ['nullable','boolean'],
        ];
    }
}
