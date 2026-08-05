<?php
	
	namespace Database\Seeders;
	
	use App\Models\AgreementStatus;
	use Illuminate\Database\Seeder;
	
	class AgreementStatusSeeder extends Seeder
	{
		public function run(): void
		{
			$statuses = [
            ['code' => 'DRAFT', 'name' => 'Draft', 'description' => 'Agreement is being prepared and has not entered formal review.', 'sort_order' => 10, 'is_terminal' => false],
            ['code' => 'UNDER_REVIEW', 'name' => 'Under Review', 'description' => 'Agreement is being reviewed by the relevant parties.', 'sort_order' => 20, 'is_terminal' => false],
            ['code' => 'PENDING_APPROVAL', 'name' => 'Pending Approval', 'description' => 'Agreement has completed review and is waiting for approval.', 'sort_order' => 30, 'is_terminal' => false],
            ['code' => 'APPROVED', 'name' => 'Approved', 'description' => 'Agreement has been approved but may not yet be active.', 'sort_order' => 40, 'is_terminal' => false],
            ['code' => 'ACTIVE', 'name' => 'Active', 'description' => 'Agreement is currently active and in force.', 'sort_order' => 50, 'is_terminal' => false],
            ['code' => 'EXPIRING_SOON', 'name' => 'Expiring Soon', 'description' => 'Agreement is approaching its expiry date.', 'sort_order' => 60, 'is_terminal' => false],
            ['code' => 'EXPIRED', 'name' => 'Expired', 'description' => 'Agreement has passed its expiry date.', 'sort_order' => 70, 'is_terminal' => true],
            ['code' => 'RENEWED', 'name' => 'Renewed', 'description' => 'Agreement has been renewed or superseded by a renewal.', 'sort_order' => 80, 'is_terminal' => false],
            ['code' => 'TERMINATED', 'name' => 'Terminated', 'description' => 'Agreement was ended before its natural expiry.', 'sort_order' => 90, 'is_terminal' => true],
            ['code' => 'ARCHIVED', 'name' => 'Archived', 'description' => 'Agreement is retained for reference and is no longer operational.', 'sort_order' => 100, 'is_terminal' => true],
            ['code' => 'CANCELLED', 'name' => 'Cancelled', 'description' => 'Agreement process was cancelled before activation.', 'sort_order' => 110, 'is_terminal' => true],
			];
			
			foreach ($statuses as $status) {
				AgreementStatus::updateOrCreate(
                ['code' => $status['code']],
                [
				'name' => $status['name'],
				'description' => $status['description'],
				'sort_order' => $status['sort_order'],
				'is_terminal' => $status['is_terminal'],
				'is_system_status' => true,
				'is_active' => true,
                ]
				);
			}
		}
	}
