<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UserIndexRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'search' => ['nullable','string','max:255'],
            'per_page' => ['nullable','integer','min:1','max:300'],
        ];
    }
}
