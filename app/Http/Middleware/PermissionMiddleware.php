<?php
	
	namespace App\Http\Middleware;
	
	use App\Support\ApiErrorCode;
	use App\Support\ApiResponse;
	use Closure;
	use Illuminate\Http\Request;
	
	class PermissionMiddleware
	{
		public function handle(Request $request, Closure $next, string ...$permissions)
		{
			$user = $request->user();
			
			if (!$user) {
				return ApiResponse::error(
                ApiErrorCode::UNAUTHENTICATED,
                'Unauthenticated.',
                401
				);
			}
			
			if (empty($permissions)) {
				return $next($request);
			}
			
			if (!$user->hasAnyPermission($permissions)) {
				return ApiResponse::error(
                ApiErrorCode::FORBIDDEN,
                'You do not have permission to access this module.',
                403
				);
			}
			
			return $next($request);
		}
	}	