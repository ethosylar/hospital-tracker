<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignUserSitesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('sites') || !is_array($this->sites)) {
            return;
        }

        $sites = collect($this->sites)
            ->map(function ($site) {
                if (!is_array($site)) {
                    return $site;
                }

                if (array_key_exists('access_level', $site)) {
                    $site['access_level'] = strtoupper(
                        trim((string) $site['access_level'])
                    );
                }

                return $site;
            })
            ->values()
            ->all();

        $this->merge(['sites' => $sites]);
    }

    public function rules(): array
    {
        return [
            'primary_site_id' => [
                'required',
                'integer',
                Rule::exists('lt_sites', 'id')
                    ->where(fn ($q) => $q->where('is_active', true)),
            ],

            'sites' => [
                'required',
                'array',
                'min:1',
            ],

            'sites.*.site_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('lt_sites', 'id')
                    ->where(fn ($q) => $q->where('is_active', true)),
            ],

            'sites.*.access_level' => [
                'required',
                'string',
                Rule::in(['VIEW', 'MANAGE', 'ADMIN']),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $siteIds = collect($this->input('sites', []))
                    ->pluck('site_id')
                    ->map(fn ($id) => (int) $id);

                if (!$siteIds->contains((int) $this->primary_site_id)) {
                    $validator->errors()->add(
                        'primary_site_id',
                        'primary_site_id must be included in the sites list.'
                    );
                }
            },
        ];
    }
}
