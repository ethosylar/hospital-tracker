<?php
	
	namespace App\Http\Controllers;
	
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Hash;
	use Illuminate\Validation\ValidationException;
	use App\Models\User;
	use Laravel\Sanctum\HasApiTokens;
	
	class AuthController extends Controller
	{
		public function login(Request $request)
		{
			$request->validate([
            'login'    => ['required','string'],
            'password' => ['required'],
			]);
			
			//$user = User::where('email', $request->email)->first();
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
			
			$user->tokens()->delete();
			
			$user->load([
			'roles:id,code,name',
			'department:id,code,name',
			]);
			
			$user->roles->makeHidden('pivot');
			$token = $user->createToken('angular')->plainTextToken;
			
			return response()->json([
            'token' => $token,
            'user'  => $user,
			//'roles' => $user->roles,
			]);
		}
		
		public function me(Request $request)
		{
			
			/*$user = $request->user()->load(['roles' => function ($q) {
				$q->select('lt_roles.id','lt_roles.code','lt_roles.name'); // qualify columns
			}]);*/
			
			$user = $request->user()->load([
			'roles:id,code,name',
			'department:id,code,name',
			]);
			
			// hide pivot in user.roles
			$user->roles->makeHidden('pivot');
			
			return response()->json([
			'user' => $user,
			//'roles' => $user->roles,
			]);
		}
		
		public function logout(Request $request)
		{
			$request->user()->currentAccessToken()->delete();
			return response()->json(['ok' => true]);
		}
	}							