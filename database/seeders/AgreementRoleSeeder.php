<?php
	
	namespace Database\Seeders;
	
	use App\Models\Role;
	use Illuminate\Database\Seeder;
	
	class AgreementRoleSeeder extends Seeder
	{
		public function run(): void
		{
			$roles = [
            [
			'code' => 'AGREEMENT_OWNER',
			'name' => 'Agreement Owner',
			'is_active' => true,
			'is_system_role' => false,
            ],
            [
			'code' => 'DEPARTMENT_REVIEWER',
			'name' => 'Department Reviewer',
			'is_active' => true,
			'is_system_role' => false,
            ],
            [
			'code' => 'AGREEMENT_ADMIN',
			'name' => 'Agreement Administrator',
			'is_active' => true,
			'is_system_role' => false,
            ],
            [
			'code' => 'APPROVER',
			'name' => 'Approver',
			'is_active' => true,
			'is_system_role' => false,
            ],
			];
			
			foreach ($roles as $role) {
				Role::updateOrCreate(
                ['code' => $role['code']],
                [
				'name' => $role['name'],
				'is_active' => $role['is_active'],
				'is_system_role' => $role['is_system_role'],
                ]
				);
			}
		}
	}	