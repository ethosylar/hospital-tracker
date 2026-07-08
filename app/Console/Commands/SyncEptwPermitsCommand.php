<?php
	
	namespace App\Console\Commands;
	
	use App\Services\Eptw\EptwSyncService;
	use Illuminate\Console\Command;
	use Throwable;
	
	class SyncEptwPermitsCommand extends Command
	{
		protected $signature = 'eptw:sync
        {--mode=INCREMENTAL : FULL, INCREMENTAL, or MANUAL}
        {--permit= : Fetch one ePTW form ID, for example 00411}
        {--user= : User ID to use for audit logging}';
		
		protected $description = 'Synchronize ePTW permits into Hospital Tracker';
		
		public function handle(EptwSyncService $syncService): int
		{
			$mode = strtoupper((string) $this->option('mode'));
			$permit = $this->option('permit');
			$userId = $this->option('user')
            ? (int) $this->option('user')
            : (int) config('services.eptw.system_user_id', 1);
			
			try {
				if ($permit) {
					$this->info('Fetching ePTW permit ' . $permit . '...');
					
					$run = $syncService->syncOne(
                    externalFormId: (string) $permit,
                    triggeredByUserId: $userId
					);
					} else {
					$this->info('Running ePTW ' . $mode . ' sync...');
					
					$run = $syncService->syncMany(
                    mode: $mode,
                    triggeredByUserId: $userId
					);
				}
				
				$this->info('Sync completed.');
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