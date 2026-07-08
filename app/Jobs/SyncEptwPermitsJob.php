<?php
	
	namespace App\Jobs;
	
	use App\Services\Eptw\EptwSyncService;
	use Illuminate\Contracts\Queue\ShouldQueue;
	use Illuminate\Contracts\Queue\ShouldBeUnique;
	use Illuminate\Foundation\Queue\Queueable;
	
	class SyncEptwPermitsJob implements ShouldQueue, ShouldBeUnique
	{
		use Queueable;
		
		public int $tries = 2;
		public int $timeout = 300;
		public int $uniqueFor = 600;
		
		public function __construct(
        public string $mode = 'INCREMENTAL',
        public ?string $externalFormId = null,
        public ?int $triggeredByUserId = null
		) {
			$this->mode = strtoupper($this->mode);
		}
		
		public function uniqueId(): string
		{
			return 'eptw-sync:' . $this->mode . ':' . ($this->externalFormId ?: 'all');
		}
		
		public function handle(EptwSyncService $syncService): void
		{
			if ($this->externalFormId) {
				$syncService->syncOne(
                externalFormId: $this->externalFormId,
                triggeredByUserId: $this->triggeredByUserId
				);
				
				return;
			}
			
			$syncService->syncMany(
            mode: $this->mode,
            triggeredByUserId: $this->triggeredByUserId
			);
		}
	}	