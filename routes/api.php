<?php
	
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Route;
	
	use App\Http\Controllers\Api\{
		ProjectController,
		ProjectTaskController,
		ProjectMilestoneController,
		ProjectBudgetLineController,
		ExternalRiskIssueController,
		DashboardController,
		LookupController,
		AuditLogController,
		DepartmentController,
		RoleController,
		UserController,
		ProjectStatusController,
		TaskStatusController,
		RiskIssueStatusController,
		SeverityController,
		PriorityController,
		ExternalSourceController,
		RiskIssueTypeController,
		FileController,
		ProjectCategoryController,
		ProjectBudgetAllocationController,
		ExternalPermitController,
		ProjectPermitLinkController,
		EptwImportController,
		IntegrationSyncRunController,
		PermissionController,
		EptwSyncController
		
	};
	
	use App\Http\Controllers\AuthController;
	
	Route::get('/health', fn () => response()->json(['ok' => true, 'message' => 'API is working']));
	
	/*
		|--------------------------------------------------------------------------
		| Authentication
		|--------------------------------------------------------------------------
	*/
	Route::post('/login', [AuthController::class, 'login']);
	
	Route::middleware('auth:sanctum')->group(function () {
		Route::post('/logout', [AuthController::class, 'logout']);
		Route::get('/me', [AuthController::class, 'me']);
		
		/*
			|--------------------------------------------------------------------------
			| General Lookups
			|--------------------------------------------------------------------------
		*/
		Route::get('/lookups', [LookupController::class, 'index']);
		
		/*
			|--------------------------------------------------------------------------
			| Sync ePTW
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:permits.sync')->group(function () {
			Route::post('/integrations/eptw/sync', [EptwSyncController::class, 'sync']);
			Route::post('/integrations/eptw/sync-one', [EptwSyncController::class, 'syncOne']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Dashboard
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:dashboard.view')->group(function () {
			Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Projects Read
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:projects.read')->group(function () {
			Route::get('/projects', [ProjectController::class, 'index']);
			Route::get('/projects/{project}', [ProjectController::class, 'show']);
			Route::get('/projects/{project}/gantt', [ProjectTaskController::class, 'gantt']);
			
			Route::get('/projects/{project}/milestones', [ProjectMilestoneController::class, 'index']);
			Route::get('/projects/{project}/milestones/{milestone}', [ProjectMilestoneController::class, 'show']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Projects Write
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:projects.write')->group(function () {
			Route::post('/projects', [ProjectController::class, 'store']);
			Route::put('/projects/{project}', [ProjectController::class, 'update']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Projects Delete
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:projects.delete')->group(function () {
			Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Task Write
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:tasks.write')->group(function () {
			Route::post('/projects/{project}/tasks', [ProjectTaskController::class, 'store']);
			Route::put('/tasks/{task}', [ProjectTaskController::class, 'update']);
			Route::delete('/tasks/{task}', [ProjectTaskController::class, 'destroy']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Milestone Write
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:milestones.write')->group(function () {
			Route::post('/projects/{project}/milestones', [ProjectMilestoneController::class, 'store']);
			Route::put('/projects/{project}/milestones/{milestone}', [ProjectMilestoneController::class, 'update']);
			Route::delete('/projects/{project}/milestones/{milestone}', [ProjectMilestoneController::class, 'destroy']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Files Read
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:files.read')->group(function () {
			Route::get('/projects/{project}/files', [FileController::class, 'projectIndex']);
			Route::get('/projects/{project}/files/{file}/download', [FileController::class, 'projectDownload']);
			
			Route::get('/tasks/{task}/files', [FileController::class, 'taskIndex']);
			Route::get('/tasks/{task}/files/{file}/download', [FileController::class, 'taskDownload']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Files Write
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:files.write')->group(function () {
			Route::post('/projects/{project}/files', [FileController::class, 'projectUpload']);
			Route::post('/projects/{project}/files/attach', [FileController::class, 'projectAttach']);
			Route::delete('/projects/{project}/files/{file}', [FileController::class, 'projectDetach']);
			
			Route::post('/tasks/{task}/files', [FileController::class, 'taskUpload']);
			Route::post('/tasks/{task}/files/attach', [FileController::class, 'taskAttach']);
			Route::delete('/tasks/{task}/files/{file}', [FileController::class, 'taskDetach']);
			Route::post('/tasks/{task}/files/{file}/move', [FileController::class, 'taskMove']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Budget Read
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:budget.read')->group(function () {
			Route::get('/projects/{project}/budget-lines', [ProjectBudgetLineController::class, 'index']);
			Route::get('/projects/{project}/budget-lines/{line}', [ProjectBudgetLineController::class, 'show']);
			
			Route::get('/projects/{project}/budget-allocations', [ProjectBudgetAllocationController::class, 'index']);
			Route::get('/projects/{project}/budget-allocations/{alloc}', [ProjectBudgetAllocationController::class, 'show']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Budget Write
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:budget.write')->group(function () {
			Route::post('/projects/{project}/budget-lines', [ProjectBudgetLineController::class, 'store']);
			Route::put('/projects/{project}/budget-lines/{line}', [ProjectBudgetLineController::class, 'update']);
			Route::delete('/projects/{project}/budget-lines/{line}', [ProjectBudgetLineController::class, 'destroy']);
			
			Route::post('/projects/{project}/budget-allocations', [ProjectBudgetAllocationController::class, 'store']);
			Route::put('/projects/{project}/budget-allocations/{alloc}', [ProjectBudgetAllocationController::class, 'update']);
			Route::delete('/projects/{project}/budget-allocations/{alloc}', [ProjectBudgetAllocationController::class, 'destroy']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| ePTW Routing
			|--------------------------------------------------------------------------
		*/
		/*
			|--------------------------------------------------------------------------
			| Permit Read
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:permits.read')->group(function () {
			Route::get('/external-permits', [ExternalPermitController::class, 'index']);
			Route::get('/external-permits/{permit}', [ExternalPermitController::class, 'show']);
			Route::get('/projects/{project}/permits', [ExternalPermitController::class, 'projectIndex']);
			Route::get('/tasks/{task}/permits', [ExternalPermitController::class, 'taskIndex']);
			Route::get('/projects/{project}/milestones/{milestone}/permits', [ExternalPermitController::class, 'milestoneIndex']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Permit Link
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:permits.link')->group(function () {
			Route::post('/projects/{project}/permit-links', [ProjectPermitLinkController::class, 'store']);
			Route::delete('/projects/{project}/permit-links/{link}', [ProjectPermitLinkController::class, 'destroy']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Test Permit Sync
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:permits.sync')->group(function () {
			Route::post('/integrations/eptw/import-test', [EptwImportController::class, 'store']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Auditor
			|--------------------------------------------------------------------------
		*/
		/*
			|--------------------------------------------------------------------------
			| Audit View
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:audit.view')->group(function () {
			Route::get('/audit-logs', [AuditLogController::class, 'index']);
			Route::get('/audit-logs/{id}', [AuditLogController::class, 'show']);
			
			Route::get('/integrations/eptw/sync-runs', [IntegrationSyncRunController::class, 'index']);
			Route::get('/integrations/eptw/sync-runs/{run}', [IntegrationSyncRunController::class, 'show']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Admin Section
			|--------------------------------------------------------------------------
		*/
		
		/*
			|--------------------------------------------------------------------------
			| Users Manage
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:users.manage')->group(function () {
			Route::get('/users', [UserController::class, 'index']);
			Route::post('/users', [UserController::class, 'store']);
			Route::get('/users/{user}', [UserController::class, 'show']);
			Route::put('/users/{user}', [UserController::class, 'update']);
			Route::delete('/users/{user}', [UserController::class, 'destroy']);
			Route::put('/users/{user}/roles', [UserController::class, 'syncRoles']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Roles and Users Manage
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:users.manage,roles.manage')->group(function () {
			Route::get('/roles', [RoleController::class, 'index']);
			Route::get('/roles/{role}', [RoleController::class, 'show']);
			
			Route::get('/permissions', [PermissionController::class, 'index']);
			Route::get('/permissions/{permission}', [PermissionController::class, 'show']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Permission Manage
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:roles.manage')->group(function () {
			Route::post('/roles', [RoleController::class, 'store']);
			Route::put('/roles/{role}', [RoleController::class, 'update']);
			Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
			
			Route::put('/roles/{role}/permissions', [RoleController::class, 'syncPermissions']);
			
			Route::post('/permissions', [PermissionController::class, 'store']);
			Route::put('/permissions/{permission}', [PermissionController::class, 'update']);
			Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Master Data Manage
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:masterdata.manage')->group(function () {
			Route::apiResource('departments', DepartmentController::class)->except(['create', 'edit']);
			
			Route::apiResource('project-statuses', ProjectStatusController::class)->parameters(['project-statuses' => 'status'])
			->except(['create', 'edit']);
			
			Route::apiResource('task-statuses', TaskStatusController::class)->parameters(['task-statuses' => 'status'])
			->except(['create', 'edit']);
			
			Route::apiResource('risk-statuses', RiskIssueStatusController::class)->parameters(['risk-statuses' => 'status'])
			->except(['create', 'edit']);
			
			Route::apiResource('severities', SeverityController::class)->parameters(['severities' => 'severity'])
			->except(['create', 'edit']);
			
			Route::apiResource('priorities', PriorityController::class)->except(['create', 'edit']);
			
			Route::apiResource('external-sources', ExternalSourceController::class)->parameters(['external-sources' => 'source'])
			->except(['create', 'edit']);
			
			Route::apiResource('risk-issue-types', RiskIssueTypeController::class)->parameters(['risk-issue-types' => 'type'])
			->except(['create', 'edit']);
			
			Route::apiResource('project-categories', ProjectCategoryController::class)->except(['create', 'edit']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| User & Roles Manage
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:users.manage,roles.manage')->group(function () {
			Route::get('/lookups/user-management', [LookupController::class, 'userManagement']);
		});
	});
