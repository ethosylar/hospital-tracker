<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use Illuminate\Support\Facades\DB;
	
	class LookupController extends Controller
	{
		/**
			* General lookups.
			* This is for normal project screens.
		*/
		public function index()
		{
			return response()->json([
            'departments' => DB::table('lt_departments')
			->where('is_active', 1)
			->orderBy('name')
			->get(['id', 'code', 'name']),
			
            'priorities' => DB::table('lt_priorities')
			->where('is_active', 1)
			->orderBy('sort_order')
			->get(['id', 'code', 'name', 'sort_order']),
			
            'project_statuses' => DB::table('st_project_statuses')
			->where('is_active', 1)
			->orderBy('sort_order')
			->get(['id', 'code', 'name', 'sort_order']),
			
            'task_statuses' => DB::table('st_task_statuses')
			->where('is_active', 1)
			->orderBy('sort_order')
			->get(['id', 'code', 'name', 'sort_order']),
			
            'risk_issue_statuses' => DB::table('st_risk_issue_statuses')
			->where('is_active', 1)
			->orderBy('sort_order')
			->get(['id', 'code', 'name', 'sort_order']),
			
            'severities' => DB::table('st_severities')
			->where('is_active', 1)
			->orderBy('sort_order')
			->get(['id', 'code', 'name', 'sort_order']),
			
            'risk_issue_types' => DB::table('lt_risk_issue_types')
			->where('is_active', 1)
			->orderBy('id')
			->get(['id', 'code', 'name']),
			
            'project_categories' => DB::table('lt_project_categories')
			->where('is_active', 1)
			->orderBy('sort_order')
			->orderBy('name')
			->get(['id', 'code', 'name']),
			]);
		}
		
		/**
			* User Management lookups.
			*
			* This is intentionally separate from the general /lookups endpoint.
			* It is used by screens that need users.manage or roles.manage.
		*/
		public function userManagement()
		{
			$departments = DB::table('lt_departments')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'code', 'name']);
			
			$permissions = DB::table('lt_permissions')
            ->where('is_active', 1)
            ->orderBy('module')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
			'id',
			'code',
			'name',
			'module',
			'description',
			'sort_order',
            ]);
			
			$roles = DB::table('lt_roles')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get([
			'id',
			'code',
			'name',
            ]);
			
			$rolePermissions = DB::table('lt_role_permissions as rp')
            ->join('lt_roles as r', 'r.id', '=', 'rp.role_id')
            ->join('lt_permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('r.is_active', 1)
            ->where('p.is_active', 1)
            ->orderBy('r.name')
            ->orderBy('p.module')
            ->orderBy('p.sort_order')
            ->orderBy('p.name')
            ->get([
			'rp.role_id',
			'rp.permission_id',
			'p.code as permission_code',
			'p.name as permission_name',
			'p.module as permission_module',
            ]);
			
			$permissionsByRole = $rolePermissions
            ->groupBy('role_id')
            ->map(function ($items) {
                return $items->map(function ($item) {
                    return [
					'id' => (int) $item->permission_id,
					'code' => $item->permission_code,
					'name' => $item->permission_name,
					'module' => $item->permission_module,
                    ];
				})->values();
			});
			
			$rolesWithPermissions = $roles->map(function ($role) use ($permissionsByRole) {
				return [
                'id' => (int) $role->id,
                'code' => $role->code,
                'name' => $role->name,
                'permissions' => $permissionsByRole->get($role->id, collect())->values(),
				];
			});
			
			$permissionModules = $permissions
            ->pluck('module')
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
	}	