<?php

namespace App\Http\Middleware;

use App\Models\ExternalRiskIssue;
use App\Support\ExternalRiskIssueAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureExternalRiskIssueSiteAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeIssue = $request->route('issue');

        if ($routeIssue === null) {
            return $next($request);
        }

        $issue = $routeIssue instanceof ExternalRiskIssue ? $routeIssue : ExternalRiskIssue::query()->find($routeIssue);

        if (!$issue || !ExternalRiskIssueAccess::canView($request->user(), $issue)) {
            abort(404);
        }

        if (!in_array(strtoupper($request->method()), ['GET', 'HEAD', 'OPTIONS',], true) && !ExternalRiskIssueAccess::canManage($request->user(), $issue)) {
            abort(403);
        }

        $request->route()->setParameter('issue', $issue);

        return $next($request);
    }
}
