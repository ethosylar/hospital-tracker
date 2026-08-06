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
		EptwSyncController,
		AgreementStatusController,
		AgreementTypeController,
		AgreementCategoryController,
		CounterpartyController,
		AgreementController,
		AgreementDocumentTypeController,
		AgreementFileController,
		
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
			|
			| Lookup routes are:
			|
			| - authenticated
			| - read only
			| - minimal data
			| - not management endpoints
			|
			| They intentionally do NOT require masterdata.manage.
			|
		*/
		Route::prefix('lookup')->group(
		function () {
			
			Route::get('/departments',[LookupController::class, 'departments',]);
			Route::get('/users', [LookupController::class, 'users',]);
			Route::get('/priorities', [LookupController::class, 'priorities',]);
			Route::get('/project-statuses', [LookupController::class, 'projectStatuses',]);
			Route::get('/task-statuses', [LookupController::class, 'taskStatuses',]);
			Route::get('/risk-issue-statuses', [LookupController::class, 'riskIssueStatuses',]);
			Route::get('/severities', [LookupController::class, 'severities',]);
			Route::get('/risk-issue-types', [LookupController::class, 'riskIssueTypes',]);
			Route::get('/project-categories', [LookupController::class, 'projectCategories',]);
			Route::get('/external-sources', [LookupController::class, 'externalSources',]);
			Route::get('/projects', [LookupController::class, 'projects',]);
			
			/*
				* Agreement lookups
			*/
			Route::get('/agreement-statuses', [LookupController::class, 'agreementStatuses',]);
			Route::get('/agreement-categories', [LookupController::class, 'agreementCategories',]);
			Route::get('/agreement-types', [LookupController::class, 'agreementTypes',]);
			Route::get('/counterparties', [LookupController::class, 'counterparties',]);
			Route::get('/agreement-document-types', [LookupController::class, 'agreementDocumentTypes',]);
		}
		);
		
		
		/*
			|--------------------------------------------------------------------------
			| Legacy Combined Lookup
			|--------------------------------------------------------------------------
			|
			| Keep temporarily until existing screens have migrated.
			|
		*/
		Route::get('/lookups', [LookupController::class, 'index',]);
		
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
			| External Risk Issues
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:risks.read')->group(function () {
			Route::get('/external-risk-issues', [ExternalRiskIssueController::class, 'index']);
			Route::get('/external-risk-issues/{issue}', [ExternalRiskIssueController::class, 'show']);
			
			Route::get('/projects/{project}/external-risk-issues', [ExternalRiskIssueController::class, 'projectIndex']);
			Route::get('/tasks/{task}/external-risk-issues', [ExternalRiskIssueController::class, 'taskIndex']);
			Route::get('/projects/{project}/milestones/{milestone}/external-risk-issues', [ExternalRiskIssueController::class, 'milestoneIndex']);
			Route::get('/external-permits/{permit}/risk-issues', [ExternalRiskIssueController::class, 'permitIndex']);
		});
		
		Route::middleware('permission:risks.write')->group(function () {
			Route::post('/external-risk-issues', [ExternalRiskIssueController::class, 'store']);
			Route::put('/external-risk-issues/{issue}', [ExternalRiskIssueController::class, 'update']);
			Route::delete('/external-risk-issues/{issue}', [ExternalRiskIssueController::class, 'destroy']);
			
			Route::post('/external-risk-issues/{issue}/links', [ExternalRiskIssueController::class, 'link']);
			Route::delete('/external-risk-issues/{issue}/links/{link}', [ExternalRiskIssueController::class, 'unlink']);
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
		
		/*
			|--------------------------------------------------------------------------
			| Agreement Status Manage
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:agreements.read,agreements.write,agreements.status.manage')->group(function () {
			Route::get('/agreement-statuses', [AgreementStatusController::class, 'index']);
			Route::get('/agreement-statuses/{status}', [AgreementStatusController::class, 'show']);
		});
		
		Route::middleware('permission:agreements.status.manage')->group(function () {
			Route::post('/agreement-statuses', [AgreementStatusController::class, 'store']);
			Route::put('/agreement-statuses/{status}', [AgreementStatusController::class, 'update']);
			Route::delete('/agreement-statuses/{status}', [AgreementStatusController::class, 'destroy']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Agreement Category Read
			|--------------------------------------------------------------------------
		*/
		Route::middleware(
		'permission:agreements.view.own,agreements.view.department,agreements.view.all,agreements.categories.manage'
		)->group(function () {
			Route::get(
			'/agreement-categories',
			[AgreementCategoryController::class, 'index']
			);
			
			Route::get(
			'/agreement-categories/{category}',
			[AgreementCategoryController::class, 'show']
			);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Agreement Category Management
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:agreements.categories.manage')->group(function () {
			Route::post(
			'/agreement-categories',
			[AgreementCategoryController::class, 'store']
			);
			
			Route::put(
			'/agreement-categories/{category}',
			[AgreementCategoryController::class, 'update']
			);
			
			Route::delete(
			'/agreement-categories/{category}',
			[AgreementCategoryController::class, 'destroy']
			);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Agreement Type Read
			|--------------------------------------------------------------------------
		*/
		Route::middleware(
		'permission:agreements.view.own,agreements.view.department,agreements.view.all,agreements.types.manage'
		)->group(function () {
			Route::get(
			'/agreement-types',
			[AgreementTypeController::class, 'index']
			);
			
			Route::get(
			'/agreement-types/{type}',
			[AgreementTypeController::class, 'show']
			);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Agreement Type Management
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:agreements.types.manage')->group(function () {
			Route::post(
			'/agreement-types',
			[AgreementTypeController::class, 'store']
			);
			
			Route::put(
			'/agreement-types/{type}',
			[AgreementTypeController::class, 'update']
			);
			
			Route::delete(
			'/agreement-types/{type}',
			[AgreementTypeController::class, 'destroy']
			);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Counterparty Management
			|--------------------------------------------------------------------------
		*/
		Route::middleware(
		'permission:agreements.view.own,agreements.view.department,agreements.view.all,agreements.create,agreements.counterparties.manage'
		)->group(function () {
			Route::get('/counterparties', [CounterpartyController::class, 'index']);
			Route::get('/counterparties/{counterparty}', [CounterpartyController::class, 'show']);
		});
		
		Route::middleware(
		'permission:agreements.counterparties.manage'
		)->group(function () {
			Route::post('/counterparties', [CounterpartyController::class, 'store']);
			Route::put('/counterparties/{counterparty}', [CounterpartyController::class, 'update']);
			Route::delete('/counterparties/{counterparty}', [CounterpartyController::class, 'destroy']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Agreement Read
			|--------------------------------------------------------------------------
		*/
		Route::middleware(
		'permission:agreements.view.own,agreements.view.department,agreements.view.all'
		)->group(function () {
			Route::get('/agreements', [AgreementController::class, 'index']);
			Route::get('/agreements/{agreement}', [AgreementController::class, 'show']);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Agreement Create and Edit
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:agreements.create')->group(function () {
			Route::post('/agreements', [AgreementController::class, 'store']);
		});
		
		Route::middleware('permission:agreements.edit')->group(function () {
			Route::put('/agreements/{agreement}', [AgreementController::class, 'update']);
			
			Route::post(
			'/agreements/{agreement}/review',
			[AgreementController::class, 'review']
			);
			
			Route::post(
			'/agreements/{agreement}/cancel',
			[AgreementController::class, 'cancel']
			);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Submission and Approval
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:agreements.submit')->group(function () {
			Route::post(
			'/agreements/{agreement}/submit',
			[AgreementController::class, 'submit']
			);
		});
		
		Route::middleware('permission:agreements.approve')->group(function () {
			Route::post(
			'/agreements/{agreement}/approve',
			[AgreementController::class, 'approve']
			);
			
			Route::post(
			'/agreements/{agreement}/activate',
			[AgreementController::class, 'activate']
			);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Amendment, Renewal, Termination, Archive
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:agreements.amend')->group(function () {
			Route::post(
			'/agreements/{agreement}/amend',
			[AgreementController::class, 'amend']
			);
		});
		
		Route::middleware('permission:agreements.renew')->group(function () {
			Route::post(
			'/agreements/{agreement}/renew',
			[AgreementController::class, 'renew']
			);
		});
		
		Route::middleware('permission:agreements.terminate')->group(function () {
			Route::post(
			'/agreements/{agreement}/terminate',
			[AgreementController::class, 'terminate']
			);
		});
		
		Route::middleware('permission:agreements.archive')->group(function () {
			Route::post(
			'/agreements/{agreement}/archive',
			[AgreementController::class, 'archive']
			);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Agreement Project Links
			|--------------------------------------------------------------------------
		*/
		Route::middleware('permission:agreements.projects.link')->group(function () {
			Route::post(
			'/agreements/{agreement}/project-links',
			[AgreementController::class, 'linkProject']
			);
			
			Route::delete(
			'/agreements/{agreement}/project-links/{link}',
			[AgreementController::class, 'unlinkProject']
			);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Agreement Document Type Read
			|--------------------------------------------------------------------------
		*/
		Route::middleware(
		'permission:agreements.view.own,agreements.view.department,agreements.view.all,agreements.create,agreements.documents.upload,agreements.document-types.manage'
		)->group(function () {
			Route::get(
			'/agreement-document-types',
			[AgreementDocumentTypeController::class, 'index']
			);
			
			Route::get(
			'/agreement-document-types/{documentType}',
			[AgreementDocumentTypeController::class, 'show']
			);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Agreement Document Type Management
			|--------------------------------------------------------------------------
		*/
		Route::middleware(
		'permission:agreements.document-types.manage'
		)->group(function () {
			Route::post(
			'/agreement-document-types',
			[AgreementDocumentTypeController::class, 'store']
			);
			
			Route::put(
			'/agreement-document-types/{documentType}',
			[AgreementDocumentTypeController::class, 'update']
			);
			
			Route::delete(
			'/agreement-document-types/{documentType}',
			[AgreementDocumentTypeController::class, 'destroy']
			);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Agreement Document Read / Download
			|--------------------------------------------------------------------------
		*/
		Route::middleware(
		'permission:agreements.view.own,agreements.view.department,agreements.view.all'
		)->group(function () {
			Route::get(
			'/agreements/{agreement}/documents',
			[AgreementFileController::class, 'index']
			);
			
			Route::get(
			'/agreements/{agreement}/documents/{agreementFile}',
			[AgreementFileController::class, 'show']
			);
			
			Route::get(
			'/agreements/{agreement}/documents/{agreementFile}/download',
			[AgreementFileController::class, 'download']
			);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Agreement Document Upload / Manage
			|--------------------------------------------------------------------------
		*/
		Route::middleware(
		'permission:agreements.documents.upload'
		)->group(function () {
			Route::post(
			'/agreements/{agreement}/documents',
			[AgreementFileController::class, 'store']
			);
			
			Route::put(
			'/agreements/{agreement}/documents/{agreementFile}',
			[AgreementFileController::class, 'update']
			);
			
			Route::delete(
			'/agreements/{agreement}/documents/{agreementFile}',
			[AgreementFileController::class, 'destroy']
			);
		});
		
		/*
			|--------------------------------------------------------------------------
			| Future OCR Request
			|--------------------------------------------------------------------------
		*/
		Route::middleware(
		'permission:agreements.documents.ocr'
		)->group(function () {
			Route::post(
			'/agreements/{agreement}/documents/{agreementFile}/ocr/request',
			[AgreementFileController::class, 'requestOcr']
			);
		});
		
		
	});
