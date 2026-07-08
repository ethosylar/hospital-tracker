<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\RoleSyncPermissionsRequest;
	use App\Http\Requests\StoreRoleRequest;
	use App\Http\Requests\UpdateRoleRequest;
	use App\Http\Resources\RoleResource;
	use App\Models\Permission;
	use App\Models\Role;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Validation\ValidationException;
	
	class RoleController extends Controller
	{
		public function index(Request $request)
		{
			$q = Role::query()
            ->select([
			'id',
			'code',
			'name',
			'is_active',
			'is_system_role',
			'created_at',
			'updated_at',
            ]);
			
			if ($request->boolean('include_permissions')) {
				$q->with([
                'permissions' => function ($p) {
                    $p->select([
					'lt_permissions.id',
					'lt_permissions.code',
					'lt_permissions.name',
					'lt_permissions.module',
					'lt_permissions.description',
					'lt_permissions.sort_order',
					'lt_permissions.is_active',
                    ])->orderBy('module')->orderBy('sort_order')->orderBy('name');
				},
				]);
			}
			
			if ($request->filled('is_active')) {
				$q->where('is_active', (int) $request->is_active);
			}
			
			if ($request->filled('search')) {
				$s = trim($request->search);
				
				$q->where(function ($w) use ($s) {
					$w->where('code', 'like', "%{$s}%")
                    ->orWhere('name', 'like', "%{$s}%");
				});
			}
			
			$perPage = max(1, min((int) $request->get('per_page', 20), 100));
			
			return RoleResource::collection(
            $q->orderBy('name')->paginate($perPage)
			);
		}
		
		public function show(Role $role)
		{
			$role->load([
            'permissions' => function ($p) {
                $p->select([
				'lt_permissions.id',
				'lt_permissions.code',
				'lt_permissions.name',
				'lt_permissions.module',
				'lt_permissions.description',
				'lt_permissions.sort_order',
				'lt_permissions.is_active',
                ])->orderBy('module')->orderBy('sort_order')->orderBy('name');
			},
			]);
			
			return new RoleResource($role);
		}
		
		public function store(StoreRoleRequest $request)
		{
			$data = $request->validated();
			
			$permissionIds = $data['permission_ids'] ?? null;
			unset($data['permission_ids']);
			
			$role = DB::transaction(function () use ($request, $data, $permissionIds) {
				$role = Role::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'is_active' => array_key_exists('is_active', $data)
				? (bool) $data['is_active']
				: true,
				'is_system_role' => array_key_exists('is_system_role', $data)
				? (bool) $data['is_system_role']
				: false,
				]);
				
				if (is_array($permissionIds)) {
					$permissionIds = $this->normalizePermissionIdsForRole($role, $permissionIds);
					$role->permissions()->sync($permissionIds);
				}
				
				\App\Support\Audit::log(
                $request->user()->id,
                'ROLE',
                (int) $role->id,
                'CREATE',
                [
				'code' => $role->code,
				'name' => $role->name,
				'is_active' => (int) $role->is_active,
				'is_system_role' => (int) $role->is_system_role,
				'permission_ids' => is_array($permissionIds)
				? array_values($permissionIds)
				: [],
                ]
				);
				
				return $role;
			});
			
			$role->load('permissions');
			
			return (new RoleResource($role))
            ->response()
            ->setStatusCode(201);
		}
		
		public function update(UpdateRoleRequest $request, Role $role)
		{
			$data = $request->validated();
			
			$permissionIds = null;
			
			if (array_key_exists('permission_ids', $data)) {
				$permissionIds = $data['permission_ids'];
				unset($data['permission_ids']);
			}
			
			if (array_key_exists('is_active', $data)) {
				$data['is_active'] = (bool) $data['is_active'];
			}
			
			if (array_key_exists('is_system_role', $data)) {
				$data['is_system_role'] = (bool) $data['is_system_role'];
			}
			
			$this->assertProtectedRoleCanBeUpdated($role, $data);
			
			$oldRole = $role->getOriginal();
			$oldPermissionIds = $role->permissions()
            ->pluck('lt_permissions.id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();
			
			$changed = false;
			
			DB::transaction(function () use (
            $request,
            $role,
            $data,
            $permissionIds,
            $oldRole,
            $oldPermissionIds,
            &$changed
			) {
				if (!empty($data)) {
					$role->fill($data);
					
					if ($role->isDirty()) {
						$dirty = $role->getDirty();
						$role->save();
						
						$changes = \App\Support\AuditDiff::diff($oldRole, $dirty);
						
						\App\Support\Audit::log(
                        $request->user()->id,
                        'ROLE',
                        (int) $role->id,
                        'UPDATE',
                        $changes
						);
						
						$changed = true;
					}
				}
				
				if (is_array($permissionIds)) {
					$permissionIds = $this->normalizePermissionIdsForRole($role, $permissionIds);
					
					$newPermissionIds = array_values(array_unique(array_map('intval', $permissionIds)));
					sort($newPermissionIds);
					
					if ($oldPermissionIds !== $newPermissionIds) {
						$sync = $role->permissions()->sync($newPermissionIds);
						
						\App\Support\Audit::log(
                        $request->user()->id,
                        'ROLE_PERMISSION',
                        (int) $role->id,
                        'SYNC',
                        [
						'role_snapshot' => [
						'code' => $role->code,
						'name' => $role->name,
						],
						'added_permission_ids' => array_values($sync['attached'] ?? []),
						'removed_permission_ids' => array_values($sync['detached'] ?? []),
						'from' => $oldPermissionIds,
						'to' => $newPermissionIds,
                        ]
						);
						
						$changed = true;
					}
				}
			});
			
			if (!$changed) {
				return response()->json([
                'ok' => true,
                'message' => 'No changes',
				]);
			}
			
			$role->refresh()->load('permissions');
			
			return new RoleResource($role);
		}
		
		public function destroy(Request $request, Role $role)
		{
			$this->assertRoleCanBeDeleted($role);
			
			$inUse = DB::table('dt_user_roles')
            ->where('role_id', $role->id)
            ->exists();
			
			if ($inUse) {
				$from = (int) $role->is_active;
				
				$role->update([
                'is_active' => false,
				]);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'ROLE',
                (int) $role->id,
                'DELETE',
                [
				'mode' => 'SOFT',
				'reason' => 'Role is assigned to existing users',
				'snapshot' => [
				'code' => $role->code,
				'name' => $role->name,
				'is_active' => $from,
				],
				'changes' => [
				'is_active' => [
				'from' => $from,
				'to' => 0,
				],
				],
                ]
				);
				
				return response()->json([
                'ok' => true,
                'mode' => 'SOFT',
				]);
			}
			
			$snapshot = [
            'code' => $role->code,
            'name' => $role->name,
            'is_active' => (int) $role->is_active,
            'permission_ids' => $role->permissions()
			->pluck('lt_permissions.id')
			->map(fn ($id) => (int) $id)
			->values()
			->all(),
			];
			
			DB::transaction(function () use ($role) {
				$role->permissions()->detach();
				$role->delete();
			});
			
			\App\Support\Audit::log(
            $request->user()->id,
            'ROLE',
            (int) $role->id,
            'DELETE',
            [
			'mode' => 'HARD',
			'snapshot' => $snapshot,
            ]
			);
			
			return response()->json([
            'ok' => true,
            'mode' => 'HARD',
			]);
		}
		
		public function syncPermissions(RoleSyncPermissionsRequest $request, Role $role)
		{
			$newPermissionIds = $this->normalizePermissionIdsForRole(
            $role,
            $request->validated('permission_ids')
			);
			
			$oldPermissionIds = $role->permissions()
            ->pluck('lt_permissions.id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();
			
			$sortedNew = array_values(array_unique(array_map('intval', $newPermissionIds)));
			sort($sortedNew);
			
			if ($oldPermissionIds === $sortedNew) {
				return response()->json([
                'ok' => true,
                'message' => 'No changes',
				]);
			}
			
			$sync = $role->permissions()->sync($sortedNew);
			
			\App\Support\Audit::log(
            $request->user()->id,
            'ROLE_PERMISSION',
            (int) $role->id,
            'SYNC',
            [
			'role_snapshot' => [
			'code' => $role->code,
			'name' => $role->name,
			],
			'added_permission_ids' => array_values($sync['attached'] ?? []),
			'removed_permission_ids' => array_values($sync['detached'] ?? []),
			'from' => $oldPermissionIds,
			'to' => $sortedNew,
            ]
			);
			
			$role->refresh()->load('permissions');
			
			return new RoleResource($role);
		}
		
		private function normalizePermissionIdsForRole(Role $role, array $permissionIds): array
		{
			$permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));
			
			/*
				* Safety rule:
				* ADMIN should always keep system.all.
				* This prevents accidentally locking your Admin out.
			*/
			if ($role->code === 'ADMIN') {
				$systemAllId = Permission::query()
                ->where('code', 'system.all')
                ->where('is_active', true)
                ->value('id');
				
				if ($systemAllId && !in_array((int) $systemAllId, $permissionIds, true)) {
					$permissionIds[] = (int) $systemAllId;
				}
			}
			
			return $permissionIds;
		}
		
		private function isProtectedRole(Role $role): bool
		{
			return $role->code === 'ADMIN' || (bool) $role->is_system_role;
		}
		
		private function assertProtectedRoleCanBeUpdated(Role $role, array $data): void
		{
			if (!$this->isProtectedRole($role)) {
				return;
			}
			
			$messages = [];
			
			if (array_key_exists('code', $data) && $data['code'] !== $role->code) {
				$messages[] = 'This protected role code cannot be changed.';
			}
			
			if (array_key_exists('name', $data) && $data['name'] !== $role->name) {
				$messages[] = 'This protected role name cannot be changed.';
			}
			
			if (array_key_exists('is_active', $data) && (bool) $data['is_active'] === false) {
				$messages[] = 'This protected role cannot be deactivated.';
			}
			
			/*
				* Once a role is protected, do not allow frontend to unprotect it.
			*/
			if (array_key_exists('is_system_role', $data) && (bool) $data['is_system_role'] === false) {
				$messages[] = 'This protected role cannot be unprotected.';
			}
			
			if (!empty($messages)) {
				throw ValidationException::withMessages([
				'role' => $messages,
				]);
			}
		}
		
		private function assertRoleCanBeDeleted(Role $role): void
		{
			if (!$this->isProtectedRole($role)) {
				return;
			}
			
			throw ValidationException::withMessages([
			'role' => [
            'This protected role cannot be deleted.',
			],
			]);
		}
		
		
	}				