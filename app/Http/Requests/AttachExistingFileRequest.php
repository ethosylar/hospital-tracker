<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttachExistingFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_id' => ['required','integer','exists:dt_files,id'],
        ];
    }
}