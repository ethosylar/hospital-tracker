<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAgreementRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	protected function prepareForValidation(): void
	{
		$data = [];

		foreach (['agreement_no', 'title', 'description', 'purpose', 'scope', 'currency_code',] as $field) {
			if (!$this->has($field)) {
				continue;
			}

			if ($this->input($field) === null) {
				$data[$field] = null;
				continue;
			}

			$value = trim((string) $this->input($field));

			$data[$field] = $value === '' ? null : $value;
		}

		if (!empty($data['agreement_no'])) {
			$data['agreement_no'] = strtoupper(
				$data['agreement_no']
			);
		}

		if (!empty($data['currency_code'])) {
			$data['currency_code'] = strtoupper(
				$data['currency_code']
			);
		}

		if (!empty($data)) {
			$this->merge($data);
		}
	}

	public function rules(): array
	{
		$agreement = $this->route('agreement');

		$agreementId = is_object($agreement) ? $agreement->id : $agreement;
		/*
         * Site used for the candidate uniqueness check:
         *
         * - incoming site_id when moving Agreement
         * - otherwise Agreement's current Site
         */
		$candidateSiteId = $this->has(
			'site_id'
		)
			? (int) $this->input('site_id')
			: (
				is_object($agreement)
				? (int) $agreement->site_id
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

			'agreement_no' => [
				'sometimes',
				'required',
				'string',
				'max:80',
				'regex:/^[A-Z0-9][A-Z0-9_\/-]*$/',

				Rule::unique(
					'dt_agreements',
					'agreement_no'
				)
					->where(
						fn($query) =>
						$query->where(
							'site_id',
							$candidateSiteId
						)
					)
					->ignore(
						$agreementId
					),
			],

			'title' => [
				'sometimes',
				'required',
				'string',
				'max:255',
			],

			'department_id' => [
				'sometimes',
				'required',
				'integer',
				'exists:lt_departments,id',
			],

			'owner_user_id' => [
				'sometimes',
				'required',
				'integer',
				'exists:users,id',
			],

			'counterparty_id' => [
				'sometimes',
				'required',
				'integer',

				Rule::exists(
					'dt_counterparties',
					'id'
				)->where(
					fn($query) =>
					$query->where(
						'is_active',
						true
					)
				),
			],

			'agreement_category_id' => [
				'sometimes',
				'required',
				'integer',

				Rule::exists(
					'lt_agreement_categories',
					'id'
				)->where(
					fn($query) =>
					$query->where(
						'is_active',
						true
					)
				),
			],

			'agreement_type_id' => [
				'sometimes',
				'nullable',
				'integer',

				Rule::exists(
					'lt_agreement_types',
					'id'
				)->where(
					fn($query) =>
					$query->where(
						'is_active',
						true
					)
				),
			],

			'description' => [
				'sometimes',
				'nullable',
				'string',
			],

			'purpose' => [
				'sometimes',
				'nullable',
				'string',
			],

			'scope' => [
				'sometimes',
				'nullable',
				'string',
			],

			'effective_date' => [
				'sometimes',
				'nullable',
				'date',
			],

			'expiry_date' => [
				'sometimes',
				'nullable',
				'date',
			],

			'signed_date' => [
				'sometimes',
				'nullable',
				'date',
			],

			'notice_period_days' => [
				'sometimes',
				'nullable',
				'integer',
				'min:0',
				'max:3650',
			],

			'auto_renewal' => [
				'sometimes',
				'boolean',
			],

			'contract_value' => [
				'sometimes',
				'nullable',
				'numeric',
				'min:0',
			],

			'currency_code' => [
				'sometimes',
				'required',
				'string',
				'size:3',
				'regex:/^[A-Z]{3}$/',
			],
		];
	}
}
