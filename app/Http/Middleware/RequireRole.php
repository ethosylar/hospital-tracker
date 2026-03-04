<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;

class RequireRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Get user's role codes
        $userRoles = DB::table('dt_user_roles as ur')
            ->join('lt_roles as r', 'r.id', '=', 'ur.role_id')
            ->where('ur.user_id', $user->id)
            ->pluck('r.code')
            ->map(fn($v) => strtoupper($v))
            ->toArray();

        // Always allow ADMIN
        if (in_array('ADMIN', $userRoles, true)) {
            return $next($request);
        }

        // If no roles specified, allow any authenticated user
        if (empty($roles)) {
            return $next($request);
        }

        $allowed = array_map('strtoupper', $roles);

        foreach ($allowed as $need) {
            if (in_array($need, $userRoles, true)) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }
}