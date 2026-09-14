<?php

namespace App\Http\Middleware;

use App\Models\IntegrationSyncRun;
use App\Support\SiteAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIntegrationSyncRunSiteAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeRun = $request->route('run');

        if ($routeRun === null) {
            return $next($request);
        }

        $run = $routeRun instanceof IntegrationSyncRun ? $routeRun : IntegrationSyncRun::query()->find($routeRun);

        if (!$run || $run->integration_code !== 'EPTW' || !SiteAccess::canViewSite($request->user(), (int) $run->site_id)) {
            abort(404);
        }

        $request->route()->setParameter('run', $run);

        return $next($request);
    }
}
