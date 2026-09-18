<?php

namespace Tests\Feature\MultiSite;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

class RouteSiteMiddlewareTest extends MultiSiteTestCase
{
    public function test_site_route_audit_command_passes(): void
    {
        $this->artisan(
            'hpms:audit-site-routes'
        )->assertExitCode(0);
    }

    public function test_previously_incorrect_routes_keep_correct_site_middleware(): void
    {
        $expectations = [
            'api/projects/{project}/external-risk-issues'
            => 'project.site',

            'api/tasks/{task}/external-risk-issues'
            => 'task.site',

            'api/projects/{project}/milestones/{milestone}/external-risk-issues'
            => 'project.site',

            'api/external-permits/{permit}/risk-issues'
            => 'permit.site',

            'api/tasks/{task}/permits'
            => 'task.site',
        ];

        foreach (
            $expectations
            as $uri => $middleware
        ) {
            $route =
                collect(
                    RouteFacade::getRoutes()
                )
                ->first(
                    fn(Route $route) =>
                    $route->uri()
                        === $uri
                );

            $this->assertNotNull(
                $route,
                "Route [{$uri}] was not found."
            );

            $middlewareList =
                $route->gatherMiddleware();

            $this->assertTrue(
                $this->middlewareExists(
                    $middlewareList,
                    $middleware
                ),
                sprintf(
                    'Route [%s] is missing middleware [%s]. Current middleware: %s',
                    $uri,
                    $middleware,
                    implode(
                        ', ',
                        $middlewareList
                    )
                )
            );
        }
    }

    private function middlewareExists(
        array $middlewareList,
        string $expected
    ): bool {
        foreach (
            $middlewareList
            as $middleware
        ) {
            if (
                $middleware === $expected
                || str_starts_with(
                    $middleware,
                    $expected . ':'
                )
            ) {
                return true;
            }
        }

        return false;
    }
}
