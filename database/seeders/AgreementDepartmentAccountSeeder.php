<?php
	
	namespace Database\Seeders;
	
	use App\Models\Role;
	use App\Models\User;
	use Illuminate\Database\Seeder;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Hash;
	use RuntimeException;
	
	class AgreementDepartmentAccountSeeder extends Seeder
	{
		/**
			* Default password requested for the imported department accounts.
			*
			* Existing users are never overwritten and their passwords are never reset
			* when this seeder is run again.
		*/
		private const DEFAULT_PASSWORD = 'abcd.1234';
		
		private const DEFAULT_ROLE_CODE = 'AGREEMENT_OWNER';
		
		public function run(): void
		{
			$agreementOwnerRole = Role::query()
            ->where('code', self::DEFAULT_ROLE_CODE)
            ->where('is_active', true)
            ->first();
			
			if (!$agreementOwnerRole) {
				throw new RuntimeException(
                'Role AGREEMENT_OWNER was not found or is inactive. '
                . 'Run AgreementRoleSeeder before AgreementDepartmentAccountSeeder.'
				);
			}
			
			$records = [
            [
			'department_code' => 'AE',
			'department_name' => 'ACCIDENT AND EMERGENCY',
			'username' => 'klgshae',
            ],
            [
			'department_code' => 'ACCOUNTS',
			'department_name' => 'ACCOUNTS',
			'username' => 'klgshac',
            ],
            [
			'department_code' => 'ADMINISTRATION',
			'department_name' => 'ADMINISTRATION',
			'username' => 'klgshadmin',
            ],
            [
			'department_code' => 'AUDIOLOGY',
			'department_name' => 'AUDIOLOGY',
			'username' => 'klgshaud',
            ],
            [
			'department_code' => 'BUSINESS_OFFICE',
			'department_name' => 'BUSINESS OFFICE',
			'username' => 'klgshbo',
            ],
            [
			'department_code' => 'CUSTOMER_SERVICE',
			'department_name' => 'CUSTOMER SERVICE EXPERIENCE',
			'username' => 'klgshcs',
            ],
            [
			'department_code' => 'DIAGNOSTIC_IMAGING',
			'department_name' => 'DIAGNOSTIC IMAGING SERVICES',
			'username' => 'klgshxray',
            ],
            [
			'department_code' => 'DIETARY',
			'department_name' => 'DIETARY',
			'username' => 'klgshdiet',
            ],
            [
			'department_code' => 'ENDOSCOPY',
			'department_name' => 'ENDOSCOPY ROOM',
			'username' => 'klgshdayward',
            ],
            [
			'department_code' => 'HAEMODIALYSIS',
			'department_name' => 'HAEMODIALYSIS',
			'username' => 'klgshdialysis',
            ],
            [
			'department_code' => 'HIMS',
			'department_name' => 'HEALTH INFORMATION MANAGEMENT SERVICES',
			'username' => 'klgshmr',
            ],
            [
			'department_code' => 'HEALTH_SCREENING',
			'department_name' => 'HEALTH SCREENING',
			'username' => 'klgshwellness',
            ],
            [
			'department_code' => 'HEALTH_TOURISM',
			'department_name' => 'HEALTH TOURISM',
			'username' => 'klgshht',
            ],
            [
			'department_code' => 'HES',
			'department_name' => 'HEALTHCARE ENGINEERING SERVICES',
			'username' => 'klgshhes',
            ],
            [
			// Reuses your existing HR department row when it already exists.
			'department_code' => 'HR',
			'department_name' => 'HUMAN RESOURCES MANAGEMENT',
			'username' => 'klgshhr',
            ],
            [
			'department_code' => 'ICU_CCU_CICU',
			'department_name' => 'ICU/CCU/CICU',
			'username' => 'klgshicu',
            ],
            [
			'department_code' => 'IT_SERVICES',
			'department_name' => 'IT Services',
			'username' => 'itservices',
            ],
            [
			'department_code' => 'WAQAF',
			'department_name' => 'KLINIK WAQAF AN-NUR',
			'username' => 'waqaf',
            ],
            [
			'department_code' => 'MARCOMM',
			'department_name' => 'BUSINESS GROWTH & MARKETING SERVICES',
			'username' => 'klgshmarketing',
            ],
            [
			'department_code' => 'MATERNITY',
			'department_name' => 'MATERNITY',
			'username' => 'klgshmat',
            ],
            [
			'department_code' => 'MEDICAL_WARD',
			'department_name' => 'MEDICAL WARD',
			'username' => 'klgshmed',
            ],
            [
			'department_code' => 'NURSING_ADMIN',
			'department_name' => 'NURSING ADMINISTRATION',
			'username' => 'klgshnurse',
            ],
            [
			'department_code' => 'OPERATION_THEATER',
			'department_name' => 'OPERATION THEATER',
			'username' => 'klgshot',
            ],
            [
			'department_code' => 'OUTSOURCE_SERVICES',
			'department_name' => 'OUTSOURCE SERVICES',
			'username' => 'klgshout',
            ],
            [
			'department_code' => 'PAEDIATRIC_WARD',
			'department_name' => 'PAEDIATRIC WARD',
			'username' => 'klgshpaed',
            ],
            [
			'department_code' => 'PATIENT_SERVICES',
			'department_name' => 'PATIENT SERVICES',
			'username' => 'klgshca',
            ],
            [
			'department_code' => 'PHARMACY',
			'department_name' => 'PHARMACY',
			'username' => 'klgshphar',
            ],
            [
			'department_code' => 'PHYSIOTHERAPY',
			'department_name' => 'PHYSIOTHERAPY',
			'username' => 'klgshphysio',
            ],
            [
			'department_code' => 'PREMIER_WARD',
			'department_name' => 'PREMIER WARD',
			'username' => 'klgshprem',
            ],
            [
			'department_code' => 'PUBLIC_RELATION',
			'department_name' => 'PUBLIC RELATION',
			'username' => 'klgshpr',
            ],
            [
			'department_code' => 'PURCHASING',
			'department_name' => 'PURCHASING',
			'username' => 'klgshpurch',
            ],
            [
			'department_code' => 'QUALITY',
			'department_name' => 'QUALITY',
			'username' => 'klgshquality',
            ],
            [
			'department_code' => 'RISK_COMPLIANCE',
			'department_name' => 'RISK & COMPLIANCE SERVICES',
			'username' => 'klgshrisk',
            ],
            [
			'department_code' => 'SAFETY_HEALTH',
			'department_name' => 'SAFETY & HEALTH',
			'username' => 'klgshsafety',
            ],
            [
			'department_code' => 'SURGICAL_WARD',
			'department_name' => 'SURGICAL WARD',
			'username' => 'klgshsurg',
            ],
			];
			
			$createdDepartments = 0;
			$reusedDepartments = 0;
			$createdUsers = 0;
			$reusedUsers = 0;
			
			DB::transaction(function () use (
            $records,
            $agreementOwnerRole,
            &$createdDepartments,
            &$reusedDepartments,
            &$createdUsers,
            &$reusedUsers
			): void {
				foreach ($records as $record) {
					$department = DB::table('lt_departments')
                    ->where('code', $record['department_code'])
                    ->first();
					
					if (!$department) {
						$departmentId = DB::table('lt_departments')
                        ->insertGetId([
						'code' => $record['department_code'],
						'name' => $record['department_name'],
						'is_active' => true,
						'created_at' => now(),
						'updated_at' => now(),
                        ]);
						
						$createdDepartments++;
						} else {
						$departmentId = (int) $department->id;
						$reusedDepartments++;
					}
					
					$email = $record['username'] . '@kpjklang.com';
					
					$user = User::query()->firstOrCreate(
                    [
					'username' => $record['username'],
                    ],
                    [
					'name' => $record['department_name'],
					'email' => $email,
					'password' => Hash::make(
					self::DEFAULT_PASSWORD
					),
					'department_id' => $departmentId,
                    ]
					);
					
					if ($user->wasRecentlyCreated) {
						$createdUsers++;
						} else {
						$reusedUsers++;
					}
					
					// Add the Agreement Owner role without removing existing roles.
					$user->roles()->syncWithoutDetaching([
                    $agreementOwnerRole->id,
					]);
				}
			});
			
			$this->command?->info(
            "Agreement department account seeding completed. "
            . "Departments created: {$createdDepartments}; "
            . "departments reused: {$reusedDepartments}; "
            . "users created: {$createdUsers}; "
            . "users reused: {$reusedUsers}."
			);
		}
	}	