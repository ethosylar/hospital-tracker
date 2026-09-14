<?php

namespace App\Http\Middleware;

use App\Models\ExternalPermit;
use App\Support\ExternalPermitAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureExternalPermitSiteAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $routePermit = $request->route('permit');

        if ($routePermit === null) {
            return $next($request);
        }

        $permit = $routePermit instanceof ExternalPermit ? $routePermit : ExternalPermit::query()->find($routePermit);

        if (!$permit || !ExternalPermitAccess::canView($request->user(), $permit)) {
            abort(404);
        }

        /*
         * Future-proof mutation routes.
         */
        if (!in_array(strtoupper($request->method()), ['GET', 'HEAD', 'OPTIONS'], true) && !ExternalPermitAccess::canManage($request->user(), $permit)) {
            abort(403);
        }

        $request->route()->setParameter('permit', $permit);

        return $next($request);
    }
}
