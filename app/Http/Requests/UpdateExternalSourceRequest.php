<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExternalSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('code')) {
            $data['code'] = strtoupper(trim((string) $this->input('code')));
        }

        if ($this->has('name')) {
            $data['name'] = trim((string) $this->input('name'));
        }

        if ($this->has('base_url')) {
            $value = $this->input('base_url');
            $data['base_url'] = $value === null ? null : trim((string) $value);
        }

        if ($data) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        $source = $this->route('source');
        $sourceId = is_object($source) ? (int) $source->id : (int) $source;
        $candidateSiteId =
            $this->has('site_id')
            ? (int) $this->input('site_id')
            : (
                is_object($source)
                ? (int) $source->site_id
                : 0
            );

        return [
            'site_id' => [
                'sometimes',
                'required',
                'integer',

                Rule::exists(
                    'lt_sites',
                    'id'
                )->where(
                    fn($query) =>
                    $query->where(
                        'is_active',
                        true
                    )
                ),
            ],

            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',

                Rule::unique(
                    'lt_external_sources',
                    'code'
                )
                    ->where(
                        fn($query) =>
                        $query->where(
                            'site_id',
                            $candidateSiteId
                        )
                    )
                    ->ignore(
                        $sourceId
                    ),
            ],

            'name' => [
                'sometimes',
                'required',
                'string',
                'max:150',
            ],

            'base_url' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
