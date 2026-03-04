<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\StoreRoleRequest;
	use App\Http\Requests\UpdateRoleRequest;
	use App\Http\Resources\RoleResource;
	use App\Models\Role;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	
	class RoleController extends Controller
	{
		public function index(Request $request)
		{
			$q = Role::query()->select(['id','code','name','is_active','created_at','updated_at']);
			
			if ($request->filled('is_active')) {
				$q->where('is_active', (int)$request->is_active);
			}
			
			if ($request->filled('search')) {
				$s = trim($request->search);
				$q->where(function ($w) use ($s) {
					$w->where('code', 'like', "%{$s}%")
					->orWhere('name', 'like', "%{$s}%");
				});
			}
			
			$perPage = max(1, min((int)$request->get('per_page', 20), 100));
			$p = $q->orderBy('name')->paginate($perPage);
			
			return RoleResource::collection($p);
		}
		
		public function show(Role $role)
		{
			return new RoleResource($role);
		}
		
		public function store(StoreRoleRequest $request)
		{
			$data = $request->validated();
			
			$role = Role::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'is_active' => array_key_exists('is_active', $data) ? (bool)$data['is_active'] : true,
			]);
			
			\App\Support\Audit::log(
            $request->user()->id,
            'ROLE',
            (int)$role->id,
            'CREATE',
            [
			'code' => $role->code,
			'name' => $role->name,
			'is_active' => (int)$role->is_active,
            ]
			);
			
			return (new RoleResource($role))
            ->response()
            ->setStatusCode(201);
		}
		
		public function update(UpdateRoleRequest $request, Role $role)
		{
			$data = $request->validated();
			
			if (array_key_exists('is_active', $data)) {
				$data['is_active'] = (bool) $data['is_active'];
			}
			
			if (empty($data)) {
				return response()->json(['ok' => true, 'message' => 'No changes']);
			}
			
			$old = $role->getOriginal();
			$changes = \App\Support\AuditDiff::diff($old, $data);
			
			if (empty($changes)) {
				return response()->json(['ok' => true, 'message' => 'No changes']);
			}
			
			$role->fill($data)->save();
			
			\App\Support\Audit::log(
			$request->user()->id,
			'ROLE',
			(int)$role->id,
			'UPDATE',
			$changes
			);
			
			return new RoleResource($role->refresh());
		}
		
		
		public function destroy(Request $request, Role $role)
		{
			// If role is used by any user, SOFT delete
			$inUse = DB::table('dt_user_roles')->where('role_id', $role->id)->exists();
			
			if ($inUse) {
				$from = (int)$role->is_active;
				$role->update(['is_active' => false]);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'ROLE',
                (int)$role->id,
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
				'is_active' => ['from' => $from, 'to' => 0],
				],
                ]
				);
				
				return response()->json(['ok' => true, 'mode' => 'SOFT']);
			}
			
			// HARD delete
			$snapshot = [
            'code' => $role->code,
            'name' => $role->name,
            'is_active' => (int)$role->is_active,
			];
			
			$id = $role->id;
			$role->delete();
			
			\App\Support\Audit::log(
            $request->user()->id,
            'ROLE',
            (int)$id,
            'DELETE',
            [
			'mode' => 'HARD',
			'snapshot' => $snapshot,
            ]
			);
			
			return response()->json(['ok' => true, 'mode' => 'HARD']);
		}
	}
