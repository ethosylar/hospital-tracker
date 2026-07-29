<?php
	
	namespace Database\Seeders;
	
	use App\Models\Permission;
	use App\Models\Role;
	use Illuminate\Database\Seeder;
	
	class RolePermissionSeeder extends Seeder
	{
		public function run(): void
		{
			$map = [
            'ADMIN' => [
			'system.all',
            ],
			
            'PMO' => [
			'dashboard.view',
			'projects.read',
			'projects.write',
			'projects.delete',
			'tasks.write',
			'milestones.write',
			'files.read',
			'files.write',
			'budget.read',
			'budget.write',
			'risks.read',
			'risks.write',
			'permits.read',
			'permits.link',
			'agreements.read',
			'agreements.write',
			'agreements.delete',
			'agreements.approve',
			'agreements.renew',
			'agreements.terminate',
			'agreements.archive',
			'agreements.files.read',
			'agreements.files.write',
            ],
			
            'PM' => [
			'dashboard.view',
			'projects.read',
			'projects.write',
			'tasks.write',
			'milestones.write',
			'files.read',
			'files.write',
			'budget.read',
			'budget.write',
			'risks.read',
			'risks.write',
			'permits.read',
			'permits.link',
			'agreements.read',
			'agreements.write',
			'agreements.files.read',
			'agreements.files.write',
            ],
			
            'AUDITOR' => [
			'dashboard.view',
			'projects.read',
			'files.read',
			'budget.read',
			'risks.read',
			'permits.read',
			'audit.view',
			'agreements.read',
			'agreements.files.read',
            ],
			
            'STAFF' => [
			'dashboard.view',
			'projects.read',
			'files.read',
			'budget.read',
			'risks.read',
			'permits.read',
			'agreements.read',
			'agreements.files.read',
            ],
			];
			
			foreach ($map as $roleCode => $permissionCodes) {
				$role = Role::where('code', $roleCode)->first();
				
				if (!$role) {
					continue;
				}
				
				$permissionIds = Permission::query()
                ->whereIn('code', $permissionCodes)
                ->pluck('id')
                ->all();
				
				$role->permissions()->sync($permissionIds);
			}
		}
	}		