<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Support\ProjectAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectSiteAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeProject = $request->route('project');

        if ($routeProject === null) {
            return $next($request);
        }

        $project = $routeProject instanceof Project
            ? $routeProject
            : Project::query()->find($routeProject);

        if (!$project || !ProjectAccess::canView($request->user(), $project)) {
            abort(404);
        }

        if (!in_array(strtoupper($request->method()), ['GET', 'HEAD', 'OPTIONS'], true) && !ProjectAccess::canManage($request->user(), $project)) {
            abort(403);
        }

        $request->route()->setParameter('project', $project);

        return $next($request);
    }
}
