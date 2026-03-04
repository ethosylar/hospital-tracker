<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\Api\UserIndexRequest;
	use App\Http\Requests\Api\UserStoreRequest;
	use App\Http\Requests\Api\UserUpdateRequest;
	use App\Http\Requests\Api\UserSyncRolesRequest;
	use App\Http\Resources\UserResource;
	use App\Models\User;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Hash;
	
	class UserController extends Controller
	{
		public function index(UserIndexRequest $request)
		{
			$perPage = (int)($request->validated('per_page') ?? 20);
			$search = trim((string)($request->validated('search') ?? ''));
			
			$q = User::query()
            ->select(['id','name','username','email','department_id','created_at','updated_at'])
            ->with([
			'roles:id,code,name',
			'department:id,code,name',
			]); // eager-load roles
			
			if ($search !== '') {
				$q->where(function ($w) use ($search) {
					$w->where('name', 'like', "%{$search}%")
					->orWhere('email', 'like', "%{$search}%");
				});
			}
			
			$page = $q->orderBy('name')->paginate($perPage);
			
			return UserResource::collection($page);
		}
		
		public function show($user)
		{
			$u = User::query()
            ->select(['id','name','username','email','department_id','created_at','updated_at'])
            ->with([
			'roles:id,code,name',
			'department:id,code,name',
			])
            ->find($user);
			
			if (!$u) return response()->json(['message' => 'Not found'], 404);
			
			return new UserResource($u);
		}
		
		public function store(UserStoreRequest $request)
		{
			$data = $request->validated();
			$roleIds = $data['role_ids'] ?? [];
			unset($data['role_ids']);
			
			$created = DB::transaction(function () use ($data, $roleIds) {
				$u = new User();
				$u->name = $data['name'];
				$u->username = $data['username'];
				$u->email = $data['email'];
				$u->password = Hash::make($data['password']);
				$u->department_id = $data['department_id'];
				$u->save();
				
				if (!empty($roleIds)) {
					$u->roles()->sync($roleIds);
					$u->load('roles:id,code,name');
				}
				
				return $u;
			});
			
			\App\Support\Audit::log(
            $request->user()->id,
            'USER',
            (int)$created->id,
            'CREATE',
            [
			'name' => $created->name,
			'username' => $created->username,
			'email' => $created->email,
			'roles' => array_values($roleIds),
			'department_id' => $created->department_id,
            ]
			);
			
			return (new UserResource($created))->response()->setStatusCode(201);
		}
		
		public function update(UserUpdateRequest $request, $user)
		{
			$u = User::find($user);
			if (!$u) return response()->json(['message' => 'Not found'], 404);
			
			$data = $request->validated();
			if (empty($data)) return response()->json(['ok' => true, 'message' => 'No changes']);
			
			$old = $u->getOriginal();
			
			$passwordProvided = array_key_exists('password', $data) && !empty($data['password']);
			if ($passwordProvided) {
				$u->password = Hash::make($data['password']);
			}
			
			if (array_key_exists('name', $data)) $u->name = $data['name'];
			if (array_key_exists('username', $data)) $u->username = $data['username'];
			if (array_key_exists('email', $data)) $u->email = $data['email'];
			if (array_key_exists('department_id', $data)) $u->department_id = $data['department_id'];
			
			$u->save();
			
			// audit diff (don’t log raw password)
			$changesInput = $data;
			unset($changesInput['password']);
			$changes = \App\Support\AuditDiff::diff($old, $changesInput);
			
			if ($passwordProvided) {
				$changes['password'] = ['from' => '[hidden]', 'to' => '[updated]'];
			}
			
			if (!empty($changes)) {
				\App\Support\Audit::log(
                $request->user()->id,
                'USER',
                (int)$u->id,
                'UPDATE',
                $changes
				);
			}
			
			return response()->json(['ok' => true]);
		}
		
		public function destroy($user)
		{
			$u = User::find($user);
			if (!$u) return response()->json(['message' => 'Not found'], 404);
			
			$snapshot = ['name' => $u->name, 'email' => $u->email];
			
			DB::transaction(function () use ($u) {
				$u->roles()->detach();
				$u->delete();
			});
			
			\App\Support\Audit::log(
            request()->user()->id,
            'USER',
            (int)$user,
            'DELETE',
            ['snapshot' => $snapshot]
			);
			
			return response()->json(['ok' => true]);
		}
		
		public function syncRoles(UserSyncRolesRequest $request, $user)
		{
			$u = User::with('roles:id')->find($user);
			if (!$u) return response()->json(['message' => 'Not found'], 404);
			
			$newRoleIds = array_values(array_unique($request->validated('role_ids')));
			
			$oldRoleIds = $u->roles->pluck('id')->map(fn($v) => (int)$v)->toArray();
			sort($oldRoleIds);
			
			$sortedNew = $newRoleIds;
			sort($sortedNew);
			
			if ($oldRoleIds === $sortedNew) {
				return response()->json(['ok' => true, 'message' => 'No changes']);
			}
			
			$sync = $u->roles()->sync($sortedNew);
			// $sync = ['attached'=>[], 'detached'=>[], 'updated'=>[]]
			
			\App\Support\Audit::log(
            $request->user()->id,
            'USER_ROLE',
            (int)$u->id,
            'SYNC',
            [
			'added_role_ids' => array_values($sync['attached'] ?? []),
			'removed_role_ids' => array_values($sync['detached'] ?? []),
			'from' => $oldRoleIds,
			'to' => $sortedNew,
			'user_snapshot' => [
			'name' => $u->name,
			'email' => $u->email,
			],
            ]
			);
			
			return response()->json([
            'ok' => true,
            'added_role_ids' => array_values($sync['attached'] ?? []),
            'removed_role_ids' => array_values($sync['detached'] ?? []),
			]);
		}
	}
