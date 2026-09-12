<?php

namespace App\Http\Middleware;

use App\Models\ProjectTask;
use App\Support\ProjectAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTaskSiteAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeTask = $request->route('task');

        if ($routeTask === null) {
            return $next($request);
        }

        $task = $routeTask instanceof ProjectTask
            ? $routeTask
            : ProjectTask::query()
            ->with('project')
            ->find($routeTask);

        if (!$task) {
            abort(404);
        }

        $task->loadMissing('project');

        if (!$task->project || !ProjectAccess::canView($request->user(), $task->project)) {
            abort(404);
        }

        if (!in_array(strtoupper($request->method()), ['GET', 'HEAD', 'OPTIONS'], true) && !ProjectAccess::canManage($request->user(), $task->project)) {
            abort(403);
        }

        $request->route()->setParameter('task', $task);

        return $next($request);
    }
}
