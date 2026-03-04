<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('status'); // route param name: /project-statuses/{status}

        return [
            'code' => ['sometimes','string','max:50',"unique:st_project_statuses,code,{$id}"],
            'name' => ['sometimes','string','max:150'],
            'sort_order' => ['nullable','integer','min:0'],
            'is_active' => ['nullable','boolean'],
        ];
    }
}
