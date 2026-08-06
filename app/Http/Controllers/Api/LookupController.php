<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Models\AgreementCategory;
	use App\Models\AgreementDocumentType;
	use App\Models\AgreementStatus;
	use App\Models\AgreementType;
	use App\Models\Counterparty;
	use App\Models\ExternalSource;
	use App\Models\Project;
	use App\Models\User;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	
	class LookupController extends Controller
	{
		/*
			|--------------------------------------------------------------------------
			| Legacy Combined Lookup
			|--------------------------------------------------------------------------
			|
			| Keep this temporarily because another existing frontend page may
			| still be calling GET /lookups.
			|
			| New frontend code should use the individual /lookup/* endpoints.
			|
		*/
		public function index()
		{
			return response()->json([
			'departments' =>
			$this->departmentRows(),
			
			'priorities' =>
			$this->priorityRows(),
			
			'project_statuses' =>
			$this->projectStatusRows(),
			
			'task_statuses' =>
			$this->taskStatusRows(),
			
			'risk_issue_statuses' =>
			$this->riskIssueStatusRows(),
			
			'severities' =>
			$this->severityRows(),
			
			'risk_issue_types' =>
			$this->riskIssueTypeRows(),
			
			'project_categories' =>
			$this->projectCategoryRows(),
			]);
		}
		
		
		/*
			|--------------------------------------------------------------------------
			| Department Lookup
			|--------------------------------------------------------------------------
		*/
		public function departments()
		{
			return $this->lookupResponse($this->departmentRows());
		}
		
		
		/*
			|--------------------------------------------------------------------------
			| User Lookup
			|--------------------------------------------------------------------------
			|
			| Intentionally returns only information required by dropdowns.
			|
			| Do NOT return roles, permissions, password information or other
			| User Management data from this endpoint.
			|
		*/
		public function users()
		{
			$rows = User::query()
			->select(['id','name','department_id',])
			->orderBy('name')->get();
			
			return $this->lookupResponse($rows);
		}
		
		
		/*
			|--------------------------------------------------------------------------
			| Priority Lookup
			|--------------------------------------------------------------------------
		*/
		public function priorities()
		{
			return $this->lookupResponse($this->priorityRows());
		}
		
		
		/*
			|--------------------------------------------------------------------------
			| Project Status Lookup
			|--------------------------------------------------------------------------
		*/
		public function projectStatuses()
		{
			return $this->lookupResponse($this->projectStatusRows());
		}
		
		
		/*
			|--------------------------------------------------------------------------
			| Task Status Lookup
			|--------------------------------------------------------------------------
		*/
		public function taskStatuses()
		{
			return $this->lookupResponse($this->taskStatusRows());
		}
		
		
		/*
			|--------------------------------------------------------------------------
			| Risk / Issue Status Lookup
			|--------------------------------------------------------------------------
		*/
		public function riskIssueStatuses()
		{
			return $this->lookupResponse($this->riskIssueStatusRows());
		}
		
		
		/*
			|--------------------------------------------------------------------------
			| Severity Lookup
			|--------------------------------------------------------------------------
		*/
		public function severities()
		{
			return $this->lookupResponse($this->severityRows());
		}
		
		
		/*
			|--------------------------------------------------------------------------
			| Risk / Issue Type Lookup
			|--------------------------------------------------------------------------
		*/
		public function riskIssueTypes()
		{
			return $this->lookupResponse($this->riskIssueTypeRows());
		}
		
		
		/*
			|--------------------------------------------------------------------------
			| Project Category Lookup
			|--------------------------------------------------------------------------
		*/
		public function projectCategories()
		{
			return $this->lookupResponse($this->projectCategoryRows());
		}
		
		
		/*
			|--------------------------------------------------------------------------
			| External Source Lookup
			|--------------------------------------------------------------------------
		*/
		public function externalSources()
		{
			$rows = ExternalSource::query()
			->where('is_active',true)
			->orderBy('name')
			->get(['id','code','name','base_url',]);
			
			return $this->lookupResponse($rows);
		}
		
		
		/*
			|--------------------------------------------------------------------------
			| Project Lookup
			|--------------------------------------------------------------------------
			|
			| This is useful where another module only needs:
			|
			|     Project ID
			|     Project Code
			|     Project Name
			|
			| For example Agreement -> Link Project.
			|
			| It does NOT expose the complete Project API.
			|
		*/
		public function projects()
		{
			$rows = Project::query()
			->orderBy('code')
			->orderBy('name')
			->get(['id','code','name',]);
			
			return $this->lookupResponse($rows);
		}
		
		
		/*
			|--------------------------------------------------------------------------
			| Agreement Status Lookup
			|--------------------------------------------------------------------------
		*/
		public function agreementStatuses()
		{
			$rows = AgreementStatus::query()
			->where('is_active',true)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id','code','name','is_terminal',]);
			
			return $this->lookupResponse($rows);
		}
		
		
		/*
			|--------------------------------------------------------------------------
			| Agreement Category Lookup
			|--------------------------------------------------------------------------
		*/
		public function agreementCategories()
		{
			$rows = AgreementCategory::query()
			->where('is_active',true)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id','code','name',]);
			
			return $this->lookupResponse($rows);
		}
		
		
		/*
			|--------------------------------------------------------------------------
			| Agreement Type Lookup
			|--------------------------------------------------------------------------
			|
			| Optional:
			|
			|     ?agreement_category_id=5
			|
			| This allows the frontend to retrieve only types belonging to a
			| selected Agreement Category if you later prefer server filtering.
			|
		*/
		public function agreementTypes(
		Request $request
		) {
			$query = AgreementType::query()
			->where('is_active',true);
			
			if ($request->filled('agreement_category_id')) {
				$query->where('agreement_category_id',(int) $request->input('agreement_category_id'));
			}
			
			$rows = $query
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id','agreement_category_id','code','name',]);
			
			return $this->lookupResponse($rows);
		}
		
		
		/*
			|--------------------------------------------------------------------------
			| Counterparty Lookup
			|--------------------------------------------------------------------------
		*/
		public function counterparties()
		{
			$rows = Counterparty::query()
			->where('is_active',true)
			->orderBy('legal_name')
			->get(['id','code','counterparty_type','legal_name','trading_name',]);
			
			return $this->lookupResponse($rows);
		}
		
		
		/*
			|--------------------------------------------------------------------------
			| Agreement Document Type Lookup
			|--------------------------------------------------------------------------
		*/
		public function agreementDocumentTypes()
		{
			$rows = AgreementDocumentType::query()
			->where('is_active',true)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id','code','name','ocr_eligible',]);
			
			return $this->lookupResponse($rows);
		}
		
		
		/*
			|--------------------------------------------------------------------------
			| User Management Lookups
			|--------------------------------------------------------------------------
			|
			| Keep this separate.
			|
			| This endpoint contains Roles and Permissions and therefore should
			| remain behind users.manage / roles.manage.
			|
		*/
		public function userManagement()
		{
			$departments = DB::table('lt_departments')
			->where('is_active',1)
			->orderBy('name')
			->get(['id','code','name',]);
			
			$permissions = DB::table('lt_permissions')
			->where('is_active',1)
			->orderBy('module')
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id','code','name','module','description','sort_order',]);
			
			$roles = DB::table('lt_roles')
			->where('is_active',1)
			->orderBy('name')
			->get(['id','code','name',]);
			
			$rolePermissions = DB::table('lt_role_permissions as rp')
			->join('lt_roles as r', 'r.id', '=', 'rp.role_id')
			->join('lt_permissions as p', 'p.id', '=', 'rp.permission_id')
			->where('r.is_active',1)
			->where('p.is_active',1)
			->orderBy('r.name')
			->orderBy('p.module')
			->orderBy('p.sort_order')
			->orderBy('p.name')
			->get(['rp.role_id','rp.permission_id','p.code as permission_code','p.name as permission_name','p.module as permission_module',]);
			
			$permissionsByRole = $rolePermissions->groupBy('role_id')
			->map(function ($items) {
				return $items->map(function ($item) {
					return [
					'id' =>
					(int) $item
					->permission_id,
					
					'code' =>
					$item
					->permission_code,
					
					'name' =>
					$item
					->permission_name,
					
					'module' =>
					$item
					->permission_module,
					];
				}
				)
				->values();
			}
			);
			
			$rolesWithPermissions = $roles
			->map(function ($role) use ($permissionsByRole) {
				return [
				'id' =>
				(int) $role->id,
				
				'code' =>
				$role->code,
				
				'name' =>
				$role->name,
				
				'permissions' =>
				$permissionsByRole
				->get(
				$role->id,
				collect()
				)
				->values(),
				];
			}
			);
			
			$permissionModules = $permissions->pluck('module')
			->filter()
			->unique()
			->values();
			
			return response()->json([
			'departments' => $departments,
			'roles' => $rolesWithPermissions,
			'permissions' => $permissions,
			'permission_modules' => $permissionModules,
			]);
		}
		
		
		/*
			|--------------------------------------------------------------------------
			| Internal Lookup Queries
			|--------------------------------------------------------------------------
		*/
		
		private function departmentRows()
		{
			return DB::table('lt_departments')
			->where('is_active',1)
			->orderBy('name')
			->get(['id','code','name',]);
		}
		
		
		private function priorityRows()
		{
			return DB::table('lt_priorities')
			->where('is_active',1)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id','code','name','sort_order',]);
		}
		
		
		private function projectStatusRows()
		{
			return DB::table('st_project_statuses')
			->where('is_active',1)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id','code','name','sort_order',]);
		}
		
		
		private function taskStatusRows()
		{
			return DB::table('st_task_statuses')
			->where('is_active',1)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id','code','name','sort_order',]);
		}
		
		
		private function riskIssueStatusRows()
		{
			return DB::table('st_risk_issue_statuses')
			->where('is_active',1)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id','code','name','sort_order',]);
		}
		
		
		private function severityRows()
		{
			return DB::table('st_severities')
			->where('is_active',1)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id','code','name','sort_order',]);
		}
		
		
		private function riskIssueTypeRows()
		{
			return DB::table('lt_risk_issue_types')
			->where('is_active',1)
			->orderBy('name')
			->get(['id','code','name',]);
		}
		
		
		private function projectCategoryRows()
		{
			return DB::table('lt_project_categories')
			->where('is_active',1)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id','code','name',]);
		}
		
		
		private function lookupResponse($rows) {
			return response()->json(['data' => $rows->values(),]);
		}
	}	