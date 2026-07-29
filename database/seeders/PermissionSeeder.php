<?php
	
	namespace Database\Seeders;
	
	use App\Models\Permission;
	use Illuminate\Database\Seeder;
	
	class PermissionSeeder extends Seeder
	{
		public function run(): void
		{
			$permissions = [
            ['code' => 'system.all', 'name' => 'Full System Access', 'module' => 'System'],
			
            ['code' => 'dashboard.view', 'name' => 'View Dashboard', 'module' => 'Dashboard'],
			
            ['code' => 'projects.read', 'name' => 'View Projects', 'module' => 'Projects'],
            ['code' => 'projects.write', 'name' => 'Create and Update Projects', 'module' => 'Projects'],
            ['code' => 'projects.delete', 'name' => 'Delete Projects', 'module' => 'Projects'],
			
            ['code' => 'tasks.write', 'name' => 'Manage Tasks', 'module' => 'Tasks'],
            ['code' => 'milestones.write', 'name' => 'Manage Milestones', 'module' => 'Milestones'],
			
            ['code' => 'files.read', 'name' => 'View Files', 'module' => 'Files'],
            ['code' => 'files.write', 'name' => 'Upload and Manage Files', 'module' => 'Files'],
			
            ['code' => 'budget.read', 'name' => 'View Budget', 'module' => 'Budget'],
            ['code' => 'budget.write', 'name' => 'Manage Budget', 'module' => 'Budget'],
			
            ['code' => 'risks.read', 'name' => 'View Risks and Issues', 'module' => 'Risks'],
            ['code' => 'risks.write', 'name' => 'Manage Risks and Issues', 'module' => 'Risks'],
			
            ['code' => 'permits.read', 'name' => 'View ePTW Permits', 'module' => 'Permits'],
            ['code' => 'permits.link', 'name' => 'Link ePTW Permits', 'module' => 'Permits'],
            ['code' => 'permits.sync', 'name' => 'Sync ePTW Permits', 'module' => 'Permits'],
			
            ['code' => 'audit.view', 'name' => 'View Audit Logs', 'module' => 'Audit'],
			
            ['code' => 'users.manage', 'name' => 'Manage Users', 'module' => 'Admin'],
            ['code' => 'roles.manage', 'name' => 'Manage Roles and Permissions', 'module' => 'Admin'],
            ['code' => 'masterdata.manage', 'name' => 'Manage Master Data', 'module' => 'Admin'],
			
			['code' => 'agreements.read', 'name' => 'View Agreements', 'module' => 'Agreements'],
			['code' => 'agreements.write', 'name' => 'Create and Update Agreements', 'module' => 'Agreements'],
			['code' => 'agreements.delete', 'name' => 'Delete Agreements', 'module' => 'Agreements'],
			['code' => 'agreements.approve', 'name' => 'Approve Agreements', 'module' => 'Agreements'],
			['code' => 'agreements.renew', 'name' => 'Renew Agreements', 'module' => 'Agreements'],
			['code' => 'agreements.terminate', 'name' => 'Terminate Agreements', 'module' => 'Agreements'],
			['code' => 'agreements.archive', 'name' => 'Archive Agreements', 'module' => 'Agreements'],
			['code' => 'agreements.files.read', 'name' => 'View Agreement Files', 'module' => 'Agreements'],
			['code' => 'agreements.files.write', 'name' => 'Upload and Manage Agreement Files', 'module' => 'Agreements'],
			['code' => 'agreements.status.manage', 'name' => 'Manage Agreement Statuses', 'module' => 'Agreements'],
			];
			
			foreach ($permissions as $index => $permission) {
				Permission::updateOrCreate(
                ['code' => $permission['code']],
                [
				'name' => $permission['name'],
				'module' => $permission['module'],
				'sort_order' => $index + 1,
				'is_active' => true,
                ]
				);
			}
		}
	}		