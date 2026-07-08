<?php
	
	namespace App\Services\Eptw;
	
	use App\Support\ApiErrorCode;
	use Illuminate\Http\Client\PendingRequest;
	use Illuminate\Support\Facades\Http;
	use RuntimeException;
	
	class EptwClient
	{
		private string $baseUrl;
		private ?string $token;
		private string $permitListPath;
		private string $permitDetailPath;
		private int $timeout;
		private int $retry;
		
		public function __construct()
		{
			$this->baseUrl = rtrim((string) config('services.eptw.base_url'), '/');
			$this->token = config('services.eptw.token') ?: null;
			$this->permitListPath = (string) config('services.eptw.permit_list_path');
			$this->permitDetailPath = (string) config('services.eptw.permit_detail_path');
			$this->timeout = (int) config('services.eptw.timeout', 30);
			$this->retry = (int) config('services.eptw.retry', 2);
			
			if ($this->baseUrl === '') {
				throw new RuntimeException('EPTW API base URL is not configured.');
			}
		}
		
		/**
			* Fetch many permits from ePTW.
			*
			* Expected future ePTW API examples:
			* GET /api/v1/permits
			* GET /api/v1/permits?updated_since=2026-07-03T10:00:00+08:00
		*/
		public function fetchPermits(?string $updatedSince = null): array
		{
			$query = [];
			
			if ($updatedSince) {
				$query['updated_since'] = $updatedSince;
			}
			
			$response = $this->http()
            ->get($this->path($this->permitListPath), $query);
			
			if (!$response->successful()) {
				throw new RuntimeException(
                'Failed to fetch ePTW permits. HTTP ' . $response->status()
				);
			}
			
			return $this->extractPermitList($response->json());
		}
		
		/**
			* Fetch one permit by ePTW form ID, for example 00411.
		*/
		public function fetchPermitByFormId(string $externalFormId): array
		{
			$externalFormId = trim($externalFormId);
			
			$path = str_replace(
            '{id}',
            urlencode($externalFormId),
            $this->permitDetailPath
			);
			
			$response = $this->http()
            ->get($this->path($path));
			
			if ($response->status() === 404) {
				throw new RuntimeException(
                'ePTW permit not found: ' . $externalFormId
				);
			}
			
			if (!$response->successful()) {
				throw new RuntimeException(
                'Failed to fetch ePTW permit ' . $externalFormId . '. HTTP ' . $response->status()
				);
			}
			
			return $this->extractPermitDetail($response->json());
		}
		
		private function http(): PendingRequest
		{
			$http = Http::baseUrl($this->baseUrl)
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
		
		/**
			* Supports several common API response shapes:
			*
			* 1. { "data": [...] }
			* 2. { "permits": [...] }
			* 3. [...]
		*/
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
		
		/**
			* Supports:
			*
			* 1. { "data": {...} }
			* 2. { "permit": {...} }
			* 3. {...}
		*/
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