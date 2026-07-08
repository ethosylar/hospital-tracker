<?php
	
	namespace App\Services\Eptw;
	
	use App\Models\ExternalSource;
	use App\Models\IntegrationSyncRun;
	use App\Services\Eptw\EptwClient;
	use App\Services\Eptw\EptwPermitImportService;
	use App\Support\ApiErrorCode;
	use App\Support\ApiResponse;
	use RuntimeException;
	
	class EptwSyncService
	{
		public function __construct(
        private readonly EptwClient $client,
        private readonly EptwPermitImportService $importService
		) {
		}
		
		public function syncMany(
        string $mode = 'INCREMENTAL',
        ?int $triggeredByUserId = null
		): IntegrationSyncRun {
			$mode = strtoupper($mode);
			
			if (!in_array($mode, ['FULL', 'INCREMENTAL', 'MANUAL'], true)) {
				throw new RuntimeException('Invalid ePTW sync mode: ' . $mode);
			}
			
			$source = $this->source();
			
			$cursorFrom = null;
			
			if ($mode === 'INCREMENTAL') {
				$cursorFrom = $this->lastSuccessfulCursor($source);
			}
			
			$cursorTo = now()->toIso8601String();
			
			$records = $this->client->fetchPermits($cursorFrom);
			
			return $this->importService->import(
            source: $source,
            records: $records,
            triggeredByUserId: $this->resolveUserId($triggeredByUserId),
            syncType: $mode,
            cursorFrom: $cursorFrom,
            cursorTo: $cursorTo
			);
		}
		
		public function syncOne(
        string $externalFormId,
        ?int $triggeredByUserId = null
		): IntegrationSyncRun {
			$source = $this->source();
			
			$record = $this->client->fetchPermitByFormId($externalFormId);
			
			return $this->importService->import(
            source: $source,
            records: [$record],
            triggeredByUserId: $this->resolveUserId($triggeredByUserId),
            syncType: 'SINGLE',
            cursorFrom: $externalFormId,
            cursorTo: $externalFormId
			);
		}
		
		private function source(): ExternalSource
		{
			$source = ExternalSource::query()
            ->where('code', 'EPTW')
            ->where('is_active', true)
            ->first();
			
			if (!$source) {
				throw new RuntimeException('The EPTW external source is not configured.');
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
            ->where('external_source_id', $source->id)
            ->where('integration_code', 'EPTW')
            ->whereIn('status', ['COMPLETED', 'PARTIAL'])
            ->whereIn('sync_type', ['FULL', 'INCREMENTAL', 'MANUAL'])
            ->whereNotNull('completed_at')
            ->orderByDesc('completed_at')
            ->first();
			
			if (!$lastRun) {
				return null;
			}
			
			return $lastRun->cursor_to
            ?: $lastRun->completed_at?->toIso8601String();
		}
	}	