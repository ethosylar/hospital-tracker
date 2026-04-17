<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFileUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware handles roles
    }

    public function rules(): array
    {
        return [
            // Laravel max is KB: 20480 = 20MB
            'file' => ['required', 'file', 'max:20480',
                // adjust allowlist as needed
                'mimes:pdf,doc,docx,xls,xlsx,csv,png,jpg,jpeg'
            ],
        ];
    }
}