<?php

namespace App\Http\Middleware;

use App\Models\Agreement;
use App\Support\AgreementAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAgreementSiteAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeAgreement = $request->route('agreement');

        if ($routeAgreement === null) {
            return $next($request);
        }

        $agreement = $routeAgreement instanceof Agreement
            ? $routeAgreement
            : Agreement::query()->find(
                $routeAgreement
            );

        /*
        |--------------------------------------------------------------------------
        | No visibility across Sites
        |--------------------------------------------------------------------------
        |
        | We use 404 instead of 403 when the user cannot even view the Site,
        | matching the Project Site security behaviour.
        |--------------------------------------------------------------------------
        */

        if (!$agreement || !AgreementAccess::canView($request->user(), $agreement)) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | Mutation requires MANAGE on Site
        |--------------------------------------------------------------------------
        */

        $readMethods = [
            'GET',
            'HEAD',
            'OPTIONS',
        ];

        if (!in_array(strtoupper($request->method()), $readMethods, true) && !AgreementAccess::canManage($request->user(), $agreement)) {
            abort(403);
        }

        /*
        |--------------------------------------------------------------------------
        | Replace route parameter with resolved Agreement model
        |--------------------------------------------------------------------------
        */

        $request->route()->setParameter('agreement', $agreement);

        return $next($request);
    }
}
