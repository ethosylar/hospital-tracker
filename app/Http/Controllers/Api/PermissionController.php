<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\StorePermissionRequest;
	use App\Http\Requests\UpdatePermissionRequest;
	use App\Http\Resources\PermissionResource;
	use App\Models\Permission;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Validation\ValidationException;
	
	class PermissionController extends Controller
	{
		public function index(Request $request)
		{
			$q = Permission::query()
            ->select([
			'id',
			'code',
			'name',
			'module',
			'description',
			'sort_order',
			'is_active',
			'created_at',
			'updated_at',
            ]);
			
			if ($request->filled('is_active')) {
				$q->where('is_active', (int) $request->is_active);
			}
			
			if ($request->filled('module')) {
				$q->where('module', $request->module);
			}
			
			if ($request->filled('search')) {
				$s = trim((string) $request->search);
				
				$q->where(function ($w) use ($s) {
					$w->where('code', 'like', "%{$s}%")
                    ->orWhere('name', 'like', "%{$s}%")
                    ->orWhere('module', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
				});
			}
			
			$perPage = max(1, min((int) $request->get('per_page', 50), 100));
			
			return PermissionResource::collection(
            $q->orderBy('module')
			->orderBy('sort_order')
			->orderBy('name')
			->paginate($perPage)
			);
		}
		
		public function show(Permission $permission)
		{
			return new PermissionResource($permission);
		}
		
		public function store(StorePermissionRequest $request)
		{
			$data = $request->validated();
			
			$permission = Permission::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'module' => $data['module'] ?? null,
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => array_key_exists('is_active', $data)
			? (bool) $data['is_active']
			: true,
			]);
			
			\App\Support\Audit::log(
            $request->user()->id,
            'PERMISSION',
            (int) $permission->id,
            'CREATE',
            [
			'code' => $permission->code,
			'name' => $permission->name,
			'module' => $permission->module,
			'is_active' => (int) $permission->is_active,
            ]
			);
			
			return (new PermissionResource($permission))
            ->response()
            ->setStatusCode(201);
		}
		
		public function update(UpdatePermissionRequest $request, Permission $permission)
		{
			$data = $request->validated();
			
			if ($permission->code === 'system.all' && array_key_exists('code', $data) && $data['code'] !== 'system.all') {
				throw ValidationException::withMessages([
                'code' => ['The system.all permission code cannot be changed.'],
				]);
			}
			
			if (array_key_exists('is_active', $data)) {
				$data['is_active'] = (bool) $data['is_active'];
			}
			
			if (empty($data)) {
				return response()->json([
                'ok' => true,
                'message' => 'No changes',
				]);
			}
			
			$old = $permission->getOriginal();
			
			$permission->fill($data);
			
			if (!$permission->isDirty()) {
				return response()->json([
                'ok' => true,
                'message' => 'No changes',
				]);
			}
			
			$dirty = $permission->getDirty();
			$permission->save();
			
			$changes = \App\Support\AuditDiff::diff($old, $dirty);
			
			\App\Support\Audit::log(
            $request->user()->id,
            'PERMISSION',
            (int) $permission->id,
            'UPDATE',
            $changes
			);
			
			return new PermissionResource($permission->refresh());
		}
		
		public function destroy(Request $request, Permission $permission)
		{
			if ($permission->code === 'system.all') {
				throw ValidationException::withMessages([
                'permission' => ['The system.all permission cannot be deleted.'],
				]);
			}
			
			$inUse = DB::table('lt_role_permissions')
            ->where('permission_id', $permission->id)
            ->exists();
			
			if ($inUse) {
				$from = (int) $permission->is_active;
				
				$permission->update([
                'is_active' => false,
				]);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'PERMISSION',
                (int) $permission->id,
                'DELETE',
                [
				'mode' => 'SOFT',
				'reason' => 'Permission is assigned to existing roles',
				'snapshot' => [
				'code' => $permission->code,
				'name' => $permission->name,
				'module' => $permission->module,
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
            'code' => $permission->code,
            'name' => $permission->name,
            'module' => $permission->module,
            'is_active' => (int) $permission->is_active,
			];
			
			$id = (int) $permission->id;
			$permission->delete();
			
			\App\Support\Audit::log(
            $request->user()->id,
            'PERMISSION',
            $id,
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
	}	