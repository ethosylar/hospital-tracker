<?php
	
	namespace App\Exceptions;
	
	//use App\Enums\ApiErrorCode;
	use App\Support\ApiErrorCode;
	use App\Support\ApiResponse;
	use Illuminate\Auth\AuthenticationException;
	use Illuminate\Auth\Access\AuthorizationException;
	use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
	use Illuminate\Validation\ValidationException;
	use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
	use Illuminate\Database\Eloquent\ModelNotFoundException;
	use Illuminate\Database\QueryException;
	use Illuminate\Http\Request;
	use Throwable;
	use Illuminate\Support\Str;
	
	class Handler extends ExceptionHandler
	{
		public function register(): void
		{
			$this->renderable(function (ValidationException $e, Request $request) {
				if (!$request->expectsJson()) return null;
				
				return response()->json([
				'ok' => false,
				'error' => [
                'code' => ApiErrorCode::VALIDATION_FAILED,
                'message' => 'Validation failed',
                'fields' => $e->errors(),
				],
				], 422);
			});
			
			$this->renderable(function (AuthenticationException $e, Request $request) {
				if (!$request->expectsJson()) return null;
				
				return response()->json([
				'ok' => false,
				'error' => [
                'code' => ApiErrorCode::UNAUTHORIZED,
                'message' => 'Unauthenticated',
				],
				], 401);
			});
			
			$this->renderable(function (AuthorizationException $e, Request $request) {
				if (!$request->expectsJson()) return null;
				
				return response()->json([
				'ok' => false,
				'error' => [
                'code' => ApiErrorCode::FORBIDDEN,
                'message' => 'Forbidden',
				],
				], 403);
			});
			
			$this->renderable(function (ModelNotFoundException $e, Request $request) {
				if (!$request->expectsJson()) return null;
				
				return response()->json([
				'ok' => false,
				'error' => [
                'code' => ApiErrorCode::NOT_FOUND,
                'message' => 'Resource not found',
				],
				], 404);
			});
			
			$this->renderable(function (QueryException $e, Request $request) {
				if (!$request->expectsJson()) return null;
				
				return response()->json([
				'ok' => false,
				'error' => [
                'code' => ApiErrorCode::DB_ERROR,
                'message' => 'Database error',
                'debug' => config('app.debug') ? [
				'sql_state' => $e->errorInfo[0] ?? null,
				'driver_code' => $e->errorInfo[1] ?? null,
                ] : null,
				],
				], 500);
			});
			
			$this->renderable(function (\Throwable $e, Request $request) {
				if (!$request->expectsJson()) return null;
				
				return response()->json([
				'ok' => false,
				'error' => [
                'code' => ApiErrorCode::SERVER_ERROR,
                'message' => 'Server error',
                'debug' => config('app.debug') ? [
				'exception' => get_class($e),
				'message' => $e->getMessage(),
                ] : null,
				],
				], 500);
			});
		}
		
	}
