<?php
	
	namespace App\Services\Eptw;
	
	class EptwStatusMapper
	{
		public function map(?string $status): string
		{
			$status = strtolower(trim((string) $status));
			
			return match ($status) {
				'pending' => 'PENDING',
				
				'in progress',
				'resume work',
				'active' => 'ACTIVE',
				
				'stop work',
				'stopped',
				'suspended' => 'SUSPENDED',
				
				'completed',
				'complete' => 'COMPLETED',
				
				'cancel',
				'cancelled',
				'canceled' => 'CANCELLED',
				
				default => 'UNKNOWN',
			};
		}
	}	