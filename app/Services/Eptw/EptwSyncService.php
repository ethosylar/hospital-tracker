<?php

namespace App\Services\Eptw;

use App\Models\ExternalSource;
use App\Models\IntegrationSyncRun;
use RuntimeException;

class EptwSyncService
{
	public function __construct(private readonly EptwClient $client, private readonly EptwPermitImportService $importService) {}

	public function syncMany(int $siteId, string $mode = 'INCREMENTAL', ?int $triggeredByUserId = null): IntegrationSyncRun
	{
		$mode = strtoupper($mode);

		if (!in_array($mode, ['FULL', 'INCREMENTAL', 'MANUAL',], true)) {
			throw new RuntimeException(
				'Invalid ePTW sync mode: '
					. $mode
			);
		}

		$source = $this->source($siteId);
		$cursorFrom = null;

		if ($mode === 'INCREMENTAL') {
			$cursorFrom = $this->lastSuccessfulCursor($source);
		}

		$cursorTo = now()->toIso8601String();
		$records = $this->client->fetchPermits($cursorFrom, $source->base_url);

		return $this->importService->import(
			source: $source,
			records: $records,
			triggeredByUserId: $this->resolveUserId(
				$triggeredByUserId
			),
			syncType: $mode,
			cursorFrom: $cursorFrom,
			cursorTo: $cursorTo
		);
	}

	public function syncOne(int $siteId, string $externalFormId, ?int $triggeredByUserId = null): IntegrationSyncRun
	{
		$source = $this->source($siteId);
		$record = $this->client->fetchPermitByFormId($externalFormId, $source->base_url);

		return $this->importService->import(
			source: $source,
			records: [$record],
			triggeredByUserId: $this->resolveUserId(
				$triggeredByUserId
			),
			syncType: 'SINGLE',
			cursorFrom: $externalFormId,
			cursorTo: $externalFormId
		);
	}

	private function source(int $siteId): ExternalSource
	{
		$source = ExternalSource::query()
			->where('site_id', $siteId)
			->where('code', 'EPTW')
			->where('is_active', true)
			->first();

		if (!$source) {
			throw new RuntimeException(
				'The EPTW external source is not configured '
					. 'for the selected Site.'
			);
		}

		return $source;
	}

	private function resolveUserId(?int $userId): int
	{
		return $userId ?: (int) config('services.eptw.system_user_id', 1);
	}

	private function lastSuccessfulCursor(ExternalSource $source): ?string
	{
		$lastRun = IntegrationSyncRun::query()
			->where('site_id', $source->site_id)
			->where('external_source_id', $source->id)
			->where('integration_code', 'EPTW')
			->whereIn('status', ['COMPLETED', 'PARTIAL',])
			->whereIn('sync_type', ['FULL', 'INCREMENTAL', 'MANUAL',])
			->whereNotNull('completed_at')
			->orderByDesc('completed_at')
			->first();

		if (!$lastRun) {
			return null;
		}

		return $lastRun->cursor_to ?: $lastRun->completed_at?->toIso8601String();
	}
}
