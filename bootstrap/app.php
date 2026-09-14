<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureProjectSiteAccess;
use App\Http\Middleware\EnsureTaskSiteAccess;
use App\Http\Middleware\EnsureAgreementSiteAccess;
use App\Http\Middleware\EnsureExternalPermitSiteAccess;
use App\Http\Middleware\EnsureIntegrationSyncRunSiteAccess;

return Application::configure(basePath: dirname(__DIR__))
	->withRouting(
		web: __DIR__ . '/../routes/web.php',
		api: __DIR__ . '/../routes/api.php',
		commands: __DIR__ . '/../routes/console.php',
		health: '/up',
	)
	->withMiddleware(function (Middleware $middleware): void {
		$middleware->alias([
			'role' => \App\Http\Middleware\RequireRole::class,
			'permission' => \App\Http\Middleware\PermissionMiddleware::class,
			'project.site' => EnsureProjectSiteAccess::class,
			'task.site' => EnsureTaskSiteAccess::class,
			'agreement.site' => EnsureAgreementSiteAccess::class,
			'permit.site' => EnsureExternalPermitSiteAccess::class,
			'sync-run.site' => EnsureIntegrationSyncRunSiteAccess::class,
		]);
	})
	->withExceptions(function (Exceptions $exceptions): void {
		//
	})->create();
