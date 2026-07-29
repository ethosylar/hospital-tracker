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
            ],
			
            'AUDITOR' => [
			'dashboard.view',
			'projects.read',
			'files.read',
			'budget.read',
			'risks.read',
			'permits.read',
			'audit.view',
            ],
			
            'STAFF' => [
			'dashboard.view',
			'projects.read',
			'files.read',
			'budget.read',
			'risks.read',
			'permits.read',
            ],
			
            /*
				* Agreement Owner:
				* Maintains agreements assigned to the user.
			*/
            'AGREEMENT_OWNER' => [
			'dashboard.view',
			'projects.read',
			
			'agreements.view.own',
			'agreements.create',
			'agreements.edit',
			'agreements.submit',
			'agreements.projects.link',
			'agreements.documents.upload',
			'agreements.renew',
			'agreements.amend',
			
			'agreements.counterparties.manage',
            ],
			
            /*
				* Department Reviewer:
				* Reviews agreements belonging to the reviewer's department.
			*/
            'DEPARTMENT_REVIEWER' => [
			'dashboard.view',
			'projects.read',
			
			'agreements.view.department',
			'agreements.edit',
			'agreements.submit',
			'agreements.documents.upload',
            ],
			
            /*
				* Agreement Administrator:
				* Manages the Agreement module across all departments.
			*/
            'AGREEMENT_ADMIN' => [
			'dashboard.view',
			'projects.read',
			
			'agreements.view.all',
			'agreements.create',
			'agreements.edit',
			'agreements.submit',
			'agreements.approve',
			'agreements.projects.link',
			'agreements.documents.upload',
			'agreements.renew',
			'agreements.terminate',
			'agreements.archive',
			'agreements.categories.manage',
			'agreements.types.manage',
			'agreements.status.manage',
			'agreements.audit.view',
			'agreements.amend',
			
			'agreements.counterparties.manage',
            ],
			
            /*
				* Approver:
				* Reviews and approves agreements for the approver's department.
			*/
            'APPROVER' => [
			'dashboard.view',
			
			'agreements.view.department',
			'agreements.approve',
			'agreements.audit.view',
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