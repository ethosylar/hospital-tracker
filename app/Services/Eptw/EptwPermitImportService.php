<?php
	
	namespace App\Services\Eptw;
	
	use App\Models\ExternalPermit;
	use App\Models\ExternalSource;
	use App\Models\IntegrationSyncRun;
	use Illuminate\Support\Facades\DB;
	use Throwable;
	
	class EptwPermitImportService
	{
		public function __construct(
        private readonly EptwPermitMapper $mapper
		) {
		}
		
		public function import(
        ExternalSource $source,
        array $records,
        int $triggeredByUserId,
        string $syncType = 'MANUAL',
        ?string $cursorFrom = null,
        ?string $cursorTo = null
		): IntegrationSyncRun {
			$run = IntegrationSyncRun::create([
            'external_source_id' => (int) $source->id,
            'integration_code' => 'EPTW',
            'sync_type' => strtoupper($syncType),
            'status' => 'RUNNING',
            'started_at' => now(),
			
            'fetched_count' => count($records),
            'created_count' => 0,
            'updated_count' => 0,
            'unchanged_count' => 0,
            'deleted_count' => 0,
            'failed_count' => 0,
			
            'cursor_from' => $cursorFrom,
            'cursor_to' => $cursorTo,
			
            'triggered_by_user_id' => $triggeredByUserId,
			]);
			
			$counts = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'deleted' => 0,
            'failed' => 0,
			];
			
			$errors = [];
			
			foreach ($records as $index => $record) {
				try {
					$result = DB::transaction(function () use (
                    $source,
                    $record,
                    $triggeredByUserId
					) {
						$mapped = $this->mapper->map($record, $source);
						$hash = $this->makeHash($mapped);
						
						$existing = ExternalPermit::query()
                        ->where(
						'external_source_id',
						$source->id
                        )
                        ->where(
						'external_form_id',
						$mapped['external_form_id']
                        )
                        ->first();
						
						$isDeleted = (bool) $mapped['is_source_deleted'];
						
						if (!$existing) {
							$permit = ExternalPermit::create([
                            ...$mapped,
							
                            'source_hash' => $hash,
                            'last_seen_at' => now(),
                            'last_synced_at' => now(),
							
                            'source_deleted_at' => $isDeleted
							? now()
							: null,
							]);
							
							\App\Support\Audit::log(
                            $triggeredByUserId,
                            'EXTERNAL_PERMIT',
                            (int) $permit->id,
                            'CREATE',
                            [
							'source' => 'EPTW',
							'external_form_id' => $permit->external_form_id,
							'external_permit_id' => $permit->external_permit_id,
							'normalized_status' => $permit->normalized_status,
							'is_source_deleted' => $permit->is_source_deleted,
                            ]
							);
							
							return $isDeleted ? 'deleted' : 'created';
						}
						
						$previousDeleted = (bool) $existing->is_source_deleted;
						
						/*
							* Hash unchanged means no business data changed.
							* last_seen_at and last_synced_at are still updated.
						*/
						if (
                        $existing->source_hash === $hash
                        && $previousDeleted === $isDeleted
						) {
							$existing->forceFill([
                            'last_seen_at' => now(),
                            'last_synced_at' => now(),
							])->save();
							
							return 'unchanged';
						}
						
						$old = $existing->getOriginal();
						
						$existing->fill([
                        ...$mapped,
						
                        'source_hash' => $hash,
                        'last_seen_at' => now(),
                        'last_synced_at' => now(),
						
                        'source_deleted_at' => $isDeleted
						? ($existing->source_deleted_at ?? now())
						: null,
						]);
						
						$dirty = $existing->getDirty();
						$existing->save();
						
						$changes = \App\Support\AuditDiff::diff(
                        $old,
                        $dirty
						);
						
						\App\Support\Audit::log(
                        $triggeredByUserId,
                        'EXTERNAL_PERMIT',
                        (int) $existing->id,
                        'UPDATE',
                        $changes
						);
						
						return $isDeleted ? 'deleted' : 'updated';
					});
					
					$counts[$result]++;
					} catch (Throwable $e) {
					report($e);
					
					$counts['failed']++;
					
					$formId = $record['external_form_id']
                    ?? $record['form_id']
                    ?? $record['id']
                    ?? ('index:' . $index);
					
					$errors[] = $formId . ': ' . $e->getMessage();
				}
			}
			
			$successfulCount =
            $counts['created']
            + $counts['updated']
            + $counts['unchanged']
            + $counts['deleted'];
			
			$status = match (true) {
				$counts['failed'] === 0 => 'COMPLETED',
				$successfulCount > 0 => 'PARTIAL',
				default => 'FAILED',
			};
			
			$run->update([
            'status' => $status,
            'completed_at' => now(),
			
            'created_count' => $counts['created'],
            'updated_count' => $counts['updated'],
            'unchanged_count' => $counts['unchanged'],
            'deleted_count' => $counts['deleted'],
            'failed_count' => $counts['failed'],
			
            'error_message' => empty($errors)
			? null
			: implode(PHP_EOL, array_slice($errors, 0, 20)),
			]);
			
			\App\Support\Audit::log(
            $triggeredByUserId,
            'EPTW_SYNC',
            (int) $run->id,
            'SYNC',
            [
			'status' => $status,
			'fetched_count' => count($records),
			'created_count' => $counts['created'],
			'updated_count' => $counts['updated'],
			'unchanged_count' => $counts['unchanged'],
			'deleted_count' => $counts['deleted'],
			'failed_count' => $counts['failed'],
            ]
			);
			
			return $run->refresh();
		}
		
		private function makeHash(array $mapped): string
		{
			/*
				* Exclude fields managed by Hospital Tracker itself.
			*/
			unset(
            $mapped['last_seen_at'],
            $mapped['last_synced_at'],
            $mapped['source_hash'],
            $mapped['source_deleted_at']
			);
			
			ksort($mapped);
			
			return hash(
            'sha256',
            json_encode(
			$mapped,
			JSON_UNESCAPED_UNICODE
			| JSON_UNESCAPED_SLASHES
			| JSON_THROW_ON_ERROR
            )
			);
		}
	}	