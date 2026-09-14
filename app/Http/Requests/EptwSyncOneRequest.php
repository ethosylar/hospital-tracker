<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EptwSyncOneRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	protected function prepareForValidation(): void
	{
		if ($this->has('external_form_id')) {
			$this->merge(['external_form_id' => trim((string) $this->input('external_form_id')),]);
		}
	}

	public function rules(): array
	{
		return [
			'site_id' => ['required', 'integer', Rule::exists('lt_sites', 'id')->where(fn($query) => $query->where('is_active', true)),],
			'external_form_id' => ['required', 'string', 'max:50',],
			'run_async' => ['nullable', 'boolean',],
		];
	}
}
