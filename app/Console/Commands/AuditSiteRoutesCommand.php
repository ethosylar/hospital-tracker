<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

class AuditSiteRoutesCommand extends Command
{
    protected $signature = 'hpms:audit-site-routes';
    protected $description = 'Check HPMS API routes for missing Multi-Site middleware';

    public function handle(): int
    {
        $problems = [];

        foreach (
            RouteFacade::getRoutes()
            as $route
        ) {
            $uri = $route->uri();

            /*
             * Ignore non-API routes.
             */
            if (!str_starts_with($uri, 'api/')) {
                continue;
            }

            $required = $this->requiredMiddleware($uri);

            if (empty($required)) {
                continue;
            }

            $middleware = $route->gatherMiddleware();

            foreach (
                $required
                as $expected
            ) {
                if (!$this->containsMiddleware($middleware, $expected)) {
                    $problems[] = [
                        implode('|', $route->methods()),
                        $uri,
                        $expected,
                        implode(', ', $middleware),
                    ];
                }
            }
        }

        if (empty($problems)) {
            $this->info('All Site-bound API routes passed the middleware audit.');
            return self::SUCCESS;
        }

        $this->error('Potential Multi-Site route security problems found:');

        $this->table(
            [
                'Method',
                'URI',
                'Missing',
                'Current Middleware',
            ],
            $problems
        );

        return self::FAILURE;
    }

    private function requiredMiddleware(string $uri): array
    {
        /*
        |--------------------------------------------------------------------------
        | Project-bound routes
        |--------------------------------------------------------------------------
        */

        if (str_contains($uri, '{project}')) {
            return ['project.site',];
        }

        /*
        |--------------------------------------------------------------------------
        | Direct Task routes
        |--------------------------------------------------------------------------
        */

        if (str_contains($uri, 'tasks/{task}')) {
            return ['task.site',];
        }

        /*
        |--------------------------------------------------------------------------
        | Agreement
        |--------------------------------------------------------------------------
        */

        if (str_contains($uri, 'agreements/{agreement}')) {
            return ['agreement.site',];
        }

        /*
        |--------------------------------------------------------------------------
        | Standalone Permit
        |--------------------------------------------------------------------------
        */

        if (str_contains($uri, 'external-permits/{permit}')) {
            return ['permit.site',];
        }

        /*
        |--------------------------------------------------------------------------
        | Risk / Issue
        |--------------------------------------------------------------------------
        */

        if (str_contains($uri, 'external-risk-issues/{issue}')) {
            return ['risk-issue.site',];
        }

        /*
        |--------------------------------------------------------------------------
        | ePTW Sync Run
        |--------------------------------------------------------------------------
        */

        if (str_contains($uri, 'integrations/eptw/sync-runs/{run}')) {
            return ['sync-run.site',];
        }

        return [];
    }

    private function containsMiddleware(array $middleware, string $expected): bool
    {
        foreach ($middleware as $item) {
            if ($item === $expected || str_starts_with($item, $expected . ':')) {
                return true;
            }
        }

        return false;
    }
}
