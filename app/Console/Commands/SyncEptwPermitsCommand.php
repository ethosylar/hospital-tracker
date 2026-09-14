<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\Eptw\EptwSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncEptwPermitsCommand extends Command
{
	protected $signature = 'eptw:sync
        {--site= : Required Site ID or Site code, e.g. KLG}
        {--mode=INCREMENTAL : FULL, INCREMENTAL, or MANUAL}
        {--permit= : Fetch one ePTW form ID, e.g. 00411}
        {--user= : User ID used for audit logging}';

	protected $description = 'Synchronize Site-specific ePTW permits into Hospital Tracker';

	public function handle(EptwSyncService $syncService): int
	{
		$siteInput = trim((string) $this->option('site'));

		if ($siteInput === '') {
			$this->error('The --site option is required. ' . 'Example: php artisan eptw:sync --site=KLG');
			return self::FAILURE;
		}

		$siteQuery = Site::query()->where('is_active', true);

		if (ctype_digit($siteInput)) {
			$siteQuery->whereKey((int) $siteInput);
		} else {
			$siteQuery->where('code', strtoupper($siteInput));
		}

		$site = $siteQuery->first();

		if (!$site) {
			$this->error('The requested Site was not found or is inactive.');
			return self::FAILURE;
		}

		$mode = strtoupper((string) $this->option('mode'));
		$permit = $this->option('permit');
		$userId = $this->option('user') ? (int) $this->option('user') : (int) config('services.eptw.system_user_id', 1);

		try {
			if ($permit) {
				$this->info("Fetching ePTW permit {$permit} " . "for {$site->code}...");

				$run = $syncService->syncOne(
					siteId: (int) $site->id,
					externalFormId: (string) $permit,
					triggeredByUserId: $userId
				);
			} else {
				$this->info("Running {$site->code} " . "ePTW {$mode} sync...");

				$run =
					$syncService->syncMany(
						siteId: (int) $site->id,
						mode: $mode,
						triggeredByUserId: $userId
					);
			}

			$this->info('Sync completed.');
			$this->line('Site: ' . $site->code);
			$this->line('Run ID: ' . $run->id);
			$this->line('Status: ' . $run->status);
			$this->line('Fetched: ' . $run->fetched_count);
			$this->line('Created: ' . $run->created_count);
			$this->line('Updated: ' . $run->updated_count);
			$this->line('Unchanged: ' . $run->unchanged_count);
			$this->line('Failed: ' . $run->failed_count);
			return self::SUCCESS;
		} catch (Throwable $e) {
			report($e);
			$this->error($e->getMessage());
			return self::FAILURE;
		}
	}
}
