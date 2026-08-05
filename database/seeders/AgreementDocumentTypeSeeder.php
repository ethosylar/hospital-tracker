<?php
	
	namespace Database\Seeders;
	
	use App\Models\AgreementDocumentType;
	use Illuminate\Database\Seeder;
	
	class AgreementDocumentTypeSeeder extends Seeder
	{
		public function run(): void
		{
			$types = [
            [
			'code' => 'DRAFT_AGREEMENT',
			'name' => 'Draft Agreement',
			'description' => 'Working or review draft of the agreement.',
			'sort_order' => 10,
            ],
            [
			'code' => 'SIGNED_AGREEMENT',
			'name' => 'Signed Agreement',
			'description' => 'Final signed or executed copy of the agreement.',
			'sort_order' => 20,
            ],
            [
			'code' => 'APPROVAL_DOCUMENT',
			'name' => 'Approval Document',
			'description' => 'Approval paper, memo, minute, or authorization document.',
			'sort_order' => 30,
            ],
            [
			'code' => 'SUPPORTING_DOCUMENT',
			'name' => 'Supporting Document',
			'description' => 'Supporting material related to the agreement.',
			'sort_order' => 40,
            ],
            [
			'code' => 'SCHEDULE_APPENDIX',
			'name' => 'Schedule / Appendix',
			'description' => 'Schedule, annexure, appendix, scope, or attachment.',
			'sort_order' => 50,
            ],
            [
			'code' => 'AMENDMENT',
			'name' => 'Amendment',
			'description' => 'Document that changes an existing agreement version.',
			'sort_order' => 60,
            ],
            [
			'code' => 'RENEWAL',
			'name' => 'Renewal',
			'description' => 'Renewal, extension, or new-term agreement document.',
			'sort_order' => 70,
            ],
            [
			'code' => 'TERMINATION_NOTICE',
			'name' => 'Termination Notice',
			'description' => 'Notice or supporting document for agreement termination.',
			'sort_order' => 80,
            ],
            [
			'code' => 'CORRESPONDENCE',
			'name' => 'Correspondence',
			'description' => 'Letter, email export, or formal correspondence.',
			'sort_order' => 90,
            ],
            [
			'code' => 'OTHER',
			'name' => 'Other',
			'description' => 'Other agreement-related document.',
			'sort_order' => 100,
            ],
			];
			
			foreach ($types as $type) {
				AgreementDocumentType::updateOrCreate(
                ['code' => $type['code']],
                [
				'name' => $type['name'],
				'description' => $type['description'],
				'ocr_eligible' => true,
				'sort_order' => $type['sort_order'],
				'is_system_type' => true,
				'is_active' => true,
                ]
				);
			}
		}
	}	