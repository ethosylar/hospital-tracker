<?php
	
	namespace App\Http\Controllers;
	
	use App\Models\User;
	use App\Support\Audit;
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
			
			Audit::log(
			(int) $user->id,
			'AUTH',
			(int) $user->id,
			'LOGIN',
			[
			'login' => $login,
			'ip' => $request->ip(),
			'user_agent' => $request->userAgent(),
			]
			);
			
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
			$user = $request->user();
			$token = $user?->currentAccessToken();
			
			if ($user) {
				Audit::log(
				(int) $user->id,
				'AUTH',
				(int) $user->id,
				'LOGOUT',
				[
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'token_id' => $token?->id,
                'token_name' => $token?->name,
				]
				);
			}
			
			$token?->delete();
			
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

			$user->loadMissing(['siteAccesses.site',]);

			$primarySiteAccess = $user->siteAccesses
				->first(fn ($access) => $access->is_primary && $access->is_active);

			$siteFields = [
				'primary_site' => $primarySiteAccess?->site ? [
					'id' => (int) $primarySiteAccess->site->id,
					'code' => $primarySiteAccess->site->code,
					'name' => $primarySiteAccess->site->name,
					'short_name' => $primarySiteAccess->site->short_name,
					'site_type' => $primarySiteAccess->site->site_type,
				] : null,

				'site_accesses' => $user->siteAccesses
					->filter(
						fn ($access) => $access->is_active
							&& $access->site?->is_active
					)
					->map(fn ($access) => [
						'site_id' => (int) $access->site_id,
						'access_level' => $access->access_level,
						'is_primary' => (bool) $access->is_primary,
						'site' => [
							'id' => (int) $access->site->id,
							'code' => $access->site->code,
							'name' => $access->site->name,
							'short_name' => $access->site->short_name,
							'site_type' => $access->site->site_type,
						],
					])
					->values(),
			];
			
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