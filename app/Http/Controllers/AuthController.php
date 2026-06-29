<?php
	
	namespace App\Http\Controllers;
	
	use App\Models\User;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Hash;
	use Illuminate\Validation\ValidationException;
	
	class AuthController extends Controller
	{
		public function login(Request $request)
		{
			$request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
			]);
			
			$login = trim($request->login);
			
			$user = User::query()
            ->where('email', $login)
            ->orWhere('username', $login)
            ->first();
			
			if (!$user || !Hash::check($request->password, $user->password)) {
				throw ValidationException::withMessages([
                'login' => ['Invalid credentials.'],
				]);
			}
			
			// Optional: only allow one active token per user
			$user->tokens()->delete();
			
			$token = $user->createToken('angular')->plainTextToken;
			
			return response()->json([
            'token' => $token,
            ...$this->authPayload($user),
			]);
		}
		
		public function me(Request $request)
		{
			return response()->json(
            $this->authPayload($request->user())
			);
		}
		
		public function logout(Request $request)
		{
			$request->user()->currentAccessToken()?->delete();
			
			return response()->json([
            'ok' => true,
			]);
		}
		
		private function authPayload(User $user): array
		{
			$user->load([
            'department:id,code,name',
			
            'roles' => function ($q) {
                $q->select(
				'lt_roles.id',
				'lt_roles.code',
				'lt_roles.name'
                );
			},
			
            'roles.permissions' => function ($q) {
                $q->select(
				'lt_permissions.id',
				'lt_permissions.code',
				'lt_permissions.name',
				'lt_permissions.module',
				'lt_permissions.is_active'
                )
                ->where('lt_permissions.is_active', true);
			},
			]);
			
			foreach ($user->roles as $role) {
				$role->makeHidden('pivot');
				
				foreach ($role->permissions as $permission) {
					$permission->makeHidden('pivot');
				}
			}
			
			$roles = $user->roles
            ->pluck('code')
            ->unique()
            ->values();
			
			$permissions = $user->roles
            ->flatMap(fn ($role) => $role->permissions)
            ->where('is_active', true)
            ->pluck('code')
            ->unique()
            ->values();
			
			return [
            'user' => $user,
            'roles' => $roles,
            'permissions' => $permissions,
			];
		}
	}	