<?php
	
	namespace Database\Seeders;
	
	use Illuminate\Database\Seeder;
	use Illuminate\Support\Facades\DB;
	
	class LookupStatusSeeder extends Seeder
	{
		/**
			* Run the database seeds.
		*/
		public function run(): void
		{
			// ---- st_project_statuses ----
			DB::table('st_project_statuses')->insertOrIgnore([
			['code' => 'PLANNED', 'name' => 'Planned', 'sort_order' => 10, 'is_active' => 1],
			['code' => 'IN_PROGRESS', 'name' => 'In Progress', 'sort_order' => 20, 'is_active' => 1],
			['code' => 'AT_RISK', 'name' => 'At Risk', 'sort_order' => 30, 'is_active' => 1],
			['code' => 'DELAYED', 'name' => 'Delayed', 'sort_order' => 40, 'is_active' => 1],
			['code' => 'ON_HOLD', 'name' => 'On Hold', 'sort_order' => 50, 'is_active' => 1],
			['code' => 'COMPLETED', 'name' => 'Completed', 'sort_order' => 60, 'is_active' => 1],
			['code' => 'CANCELLED', 'name' => 'Cancelled', 'sort_order' => 70, 'is_active' => 1],
			]);
			
			
			// ---- st_task_statuses ----
			DB::table('st_task_statuses')->insertOrIgnore([
			['code' => 'TODO', 'name' => 'To Do', 'sort_order' => 10, 'is_active' => 1],
			['code' => 'IN_PROGRESS', 'name' => 'In Progress', 'sort_order' => 20, 'is_active' => 1],
			['code' => 'BLOCKED', 'name' => 'Blocked', 'sort_order' => 30, 'is_active' => 1],
			['code' => 'DONE', 'name' => 'Done', 'sort_order' => 40, 'is_active' => 1],
			['code' => 'CANCELLED', 'name' => 'Cancelled', 'sort_order' => 50, 'is_active' => 1],
			]);
			
			
			// ---- st_risk_issue_statuses ----
			DB::table('st_risk_issue_statuses')->insertOrIgnore([
			['code' => 'OPEN', 'name' => 'Open', 'sort_order' => 10, 'is_active' => 1],
			['code' => 'MITIGATING', 'name' => 'Mitigating', 'sort_order' => 20, 'is_active' => 1],
			['code' => 'RESOLVED', 'name' => 'Resolved', 'sort_order' => 30, 'is_active' => 1],
			['code' => 'CLOSED', 'name' => 'Closed', 'sort_order' => 40, 'is_active' => 1],
			]);
			
			
			// ---- st_severities ----
			DB::table('st_severities')->insertOrIgnore([
			['code' => 'LOW', 'name' => 'Low', 'sort_order' => 10, 'is_active' => 1],
			['code' => 'MEDIUM', 'name' => 'Medium', 'sort_order' => 20, 'is_active' => 1],
			['code' => 'HIGH', 'name' => 'High', 'sort_order' => 30, 'is_active' => 1],
			['code' => 'CRITICAL', 'name' => 'Critical', 'sort_order' => 40, 'is_active' => 1],
			]);
			
			
			// ---- lt_priorities ----
			DB::table('lt_priorities')->insertOrIgnore([
			['code' => 'LOW', 'name' => 'Low', 'sort_order' => 10, 'is_active' => 1],
			['code' => 'MEDIUM', 'name' => 'Medium', 'sort_order' => 20, 'is_active' => 1],
			['code' => 'HIGH', 'name' => 'High', 'sort_order' => 30, 'is_active' => 1],
			['code' => 'CRITICAL', 'name' => 'Critical', 'sort_order' => 40, 'is_active' => 1],
			]);
			
			
			// ---- lt_risk_issue_types ----
			DB::table('lt_risk_issue_types')->insertOrIgnore([
			['code' => 'RISK', 'name' => 'Risk', 'is_active' => 1],
			['code' => 'ISSUE', 'name' => 'Issue', 'is_active' => 1],
			]);
			
			
			// ---- lt_roles ----
			DB::table('lt_roles')->insertOrIgnore([
			['code' => 'ADMIN', 'name' => 'Admin', 'is_active' => 1],
			['code' => 'PMO', 'name' => 'PMO', 'is_active' => 1],
			['code' => 'PM', 'name' => 'Project Manager', 'is_active' => 1],
			['code' => 'STAFF', 'name' => 'Staff', 'is_active' => 1],
			['code' => 'AUDITOR', 'name' => 'Auditor', 'is_active' => 1],
			]);
			
			
			// ---- lt_departments (starter set) ----
			DB::table('lt_departments')->insertOrIgnore([
			['code' => 'IT', 'name' => 'Information Technology', 'is_active' => 1],
			['code' => 'CLINICAL', 'name' => 'Clinical', 'is_active' => 1],
			['code' => 'FACILITY', 'name' => 'Facilities', 'is_active' => 1],
			['code' => 'FINANCE', 'name' => 'Finance', 'is_active' => 1],
			['code' => 'HR', 'name' => 'Human Resource', 'is_active' => 1],
			['code' => 'VENDOR', 'name' => 'Vendor / External', 'is_active' => 1],
			]);
		}
	}
