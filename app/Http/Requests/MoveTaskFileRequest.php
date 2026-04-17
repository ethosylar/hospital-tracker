<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveTaskFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to_task_id' => ['required','integer','exists:dt_project_tasks,id'],
            'keep_on_source' => ['nullable','boolean'], // default false => detach from source
        ];
    }
}