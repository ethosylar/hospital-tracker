<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EptwSyncRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	protected function prepareForValidation(): void
	{
		if (!$this->has('mode') || $this->input('mode') === null) {
			$this->merge(['mode' => 'INCREMENTAL',]);
		}

		if ($this->has('mode')) {
			$this->merge(['mode' => strtoupper(trim((string) $this->input('mode'))),]);
		}
	}

	public function rules(): array
	{
		return [
			'site_id' => ['required', 'integer', Rule::exists('lt_sites', 'id')->where(fn($query) => $query->where('is_active', true)),],
			'mode' => ['required', Rule::in(['FULL', 'INCREMENTAL', 'MANUAL',]),],
			'run_async' => ['nullable', 'boolean',],
		];
	}
}
