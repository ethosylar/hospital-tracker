<?php

namespace App\Services\Eptw;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class EptwClient
{
	private string $defaultBaseUrl;
	private ?string $token;
	private string $permitListPath;
	private string $permitDetailPath;
	private int $timeout;
	private int $retry;

	public function __construct()
	{
		$this->defaultBaseUrl = rtrim((string) config('services.eptw.base_url', ''), '/');
		$this->token = config('services.eptw.token') ?: null;
		$this->permitListPath = (string) config('services.eptw.permit_list_path');
		$this->permitDetailPath = (string) config('services.eptw.permit_detail_path');
		$this->timeout = (int) config('services.eptw.timeout', 30);
		$this->retry = (int) config('services.eptw.retry', 2);
	}

	public function fetchPermits(?string $updatedSince = null, ?string $baseUrl = null): array
	{
		$query = [];

		if ($updatedSince) {
			$query['updated_since'] = $updatedSince;
		}

		$response = $this->http($baseUrl)->get($this->path($this->permitListPath), $query);

		if (!$response->successful()) {
			throw new RuntimeException(
				'Failed to fetch ePTW permits. HTTP '
					. $response->status()
			);
		}

		return $this->extractPermitList($response->json());
	}

	public function fetchPermitByFormId(string $externalFormId, ?string $baseUrl = null): array
	{
		$externalFormId = trim($externalFormId);

		$path = str_replace('{id}', urlencode($externalFormId), $this->permitDetailPath);

		$response = $this->http($baseUrl)->get($this->path($path));

		if ($response->status() === 404) {
			throw new RuntimeException('ePTW permit not found: ' . $externalFormId);
		}

		if (!$response->successful()) {
			throw new RuntimeException(
				'Failed to fetch ePTW permit '
					. $externalFormId
					. '. HTTP '
					. $response->status()
			);
		}

		return $this->extractPermitDetail($response->json());
	}

	private function http(?string $baseUrl = null): PendingRequest
	{
		$resolvedBaseUrl = rtrim(trim((string) ($baseUrl ?: $this->defaultBaseUrl)), '/');

		if ($resolvedBaseUrl === '') {
			throw new RuntimeException('EPTW API base URL is not configured for this Site.');
		}

		$http = Http::baseUrl($resolvedBaseUrl)
			->acceptJson()
			->timeout($this->timeout)
			->retry($this->retry, 500);

		if ($this->token) {
			$http = $http->withToken($this->token);
		}

		return $http;
	}

	private function path(string $path): string
	{
		return '/' . ltrim($path, '/');
	}

	private function extractPermitList(mixed $json): array
	{
		if (is_array($json) && array_is_list($json)) {
			return $json;
		}

		if (is_array($json) && isset($json['data']) && is_array($json['data'])) {
			return $json['data'];
		}

		if (is_array($json) && isset($json['permits']) && is_array($json['permits'])) {
			return $json['permits'];
		}

		throw new RuntimeException('Invalid ePTW permit list API response.');
	}

	private function extractPermitDetail(mixed $json): array
	{
		if (is_array($json) && isset($json['data']) && is_array($json['data'])) {
			return $json['data'];
		}

		if (is_array($json) && isset($json['permit']) && is_array($json['permit'])) {
			return $json['permit'];
		}

		if (is_array($json)) {
			return $json;
		}

		throw new RuntimeException('Invalid ePTW permit detail API response.');
	}
}
