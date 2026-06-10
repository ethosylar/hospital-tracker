<?php
	
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Route;
	
	use App\Http\Controllers\Api\{
		ProjectController,
		ProjectTaskController,
		ProjectMilestoneController,
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
		FileController
	};
	
	use App\Http\Controllers\AuthController;
	
	Route::get('/health', fn () => response()->json(['ok' => true, 'message' => 'API is working']));
	
	/**
		* Auth
	*/
	Route::post('/login', [AuthController::class, 'login']);
	
	Route::middleware('auth:sanctum')->group(function () {
		Route::post('/logout', [AuthController::class, 'logout']);
		//Route::get('/me', fn (Request $request) => $request->user());
		Route::get('/me', [AuthController::class, 'me']);
		
		/**
			* Common (all authenticated users: Admin, Auditor, PMO, PM, Staff)
		*/
		Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
		Route::get('/lookups', [LookupController::class, 'index']);
		
		/**
			* Projects - READ for all authenticated users
		*/
		Route::get('/projects', [ProjectController::class, 'index']);
		Route::get('/projects/{project}', [ProjectController::class, 'show']);
		Route::get('/projects/{project}/gantt', [ProjectTaskController::class, 'gantt']);
		
		// Milestones - READ for all authenticated users
		Route::get('/projects/{project}/milestones', [ProjectMilestoneController::class, 'index']);
		Route::get('/projects/{project}/milestones/{milestone}', [ProjectMilestoneController::class, 'show']);
		
		// External Risk Issues - READ for all authenticated users (optional, but usually useful)
		Route::get('/external-risk-issues', [ExternalRiskIssueController::class, 'index']);
		Route::get('/external-risk-issues/{issue}', [ExternalRiskIssueController::class, 'show']);
		
		// File for Upload, Update, Move & Delete
		Route::get('/projects/{project}/files', [FileController::class, 'projectIndex']);
		Route::get('/projects/{project}/files/{file}/download', [FileController::class, 'projectDownload']);
		
		Route::get('/tasks/{task}/files', [FileController::class, 'taskIndex']);
		Route::get('/tasks/{task}/files/{file}/download', [FileController::class, 'taskDownload']);
		
		Route::get('/projects/{project}/budget-lines', [ProjectBudgetLineController::class, 'index']);
Route::get('/projects/{project}/budget-lines/{line}', [ProjectBudgetLineController::class, 'show']);
		
		/**
			* PMO + PM write access (project operations)
		*/
		Route::middleware('role:PMO,PM')->group(function () {
			// Projects
			Route::post('/projects', [ProjectController::class, 'store']);
			Route::put('/projects/{project}', [ProjectController::class, 'update']);
			Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
			
			// Tasks
			Route::post('/projects/{project}/tasks', [ProjectTaskController::class, 'store']);
			Route::put('/tasks/{task}', [ProjectTaskController::class, 'update']);
			Route::delete('/tasks/{task}', [ProjectTaskController::class, 'destroy']);
			
			// Milestones write
			Route::post('/projects/{project}/milestones', [ProjectMilestoneController::class, 'store']);
			Route::put('/projects/{project}/milestones/{milestone}', [ProjectMilestoneController::class, 'update']);
			Route::delete('/projects/{project}/milestones/{milestone}', [ProjectMilestoneController::class, 'destroy']);
			
			// External Risk Issue write (if PMO/PM manage them)
			Route::post('/external-risk-issues', [ExternalRiskIssueController::class, 'store']);
			Route::put('/external-risk-issues/{issue}', [ExternalRiskIssueController::class, 'update']);
			Route::delete('/external-risk-issues/{issue}', [ExternalRiskIssueController::class, 'destroy']);
			
			Route::post('/projects/{project}/files', [FileController::class, 'projectUpload']);
			Route::post('/projects/{project}/files/attach', [FileController::class, 'projectAttach']);
			Route::delete('/projects/{project}/files/{file}', [FileController::class, 'projectDetach']);
			
			Route::post('/tasks/{task}/files', [FileController::class, 'taskUpload']);
			Route::post('/tasks/{task}/files/attach', [FileController::class, 'taskAttach']);
			Route::delete('/tasks/{task}/files/{file}', [FileController::class, 'taskDetach']);
			
			Route::post('/tasks/{task}/files/{file}/move', [FileController::class, 'taskMove']);
			
		});
		
		/**
			* Auditor + Admin: audit logs
		*/
		Route::middleware('role:AUDITOR,ADMIN')->group(function () {
			Route::get('/audit-logs', [AuditLogController::class, 'index']);
			Route::get('/audit-logs/{id}', [AuditLogController::class, 'show']);
		});
		
		/**
			* Admin only: user/role/department + lookup master data maintenance
		*/
		Route::middleware('role:ADMIN')->group(function () {
			
			// Users + role assignment
			Route::get('/users', [UserController::class, 'index']);
			Route::post('/users', [UserController::class, 'store']);
			Route::get('/users/{user}', [UserController::class, 'show']);
			Route::put('/users/{user}', [UserController::class, 'update']);
			Route::delete('/users/{user}', [UserController::class, 'destroy']);
			Route::put('/users/{user}/roles', [UserController::class, 'syncRoles']);
			
			// Departments
			Route::get('/departments', [DepartmentController::class, 'index']);
			Route::post('/departments', [DepartmentController::class, 'store']);
			Route::get('/departments/{department}', [DepartmentController::class, 'show']);
			Route::put('/departments/{department}', [DepartmentController::class, 'update']);
			Route::delete('/departments/{department}', [DepartmentController::class, 'destroy']);
			
			// Roles
			Route::get('/roles', [RoleController::class, 'index']);
			Route::post('/roles', [RoleController::class, 'store']);
			Route::get('/roles/{role}', [RoleController::class, 'show']);
			Route::put('/roles/{role}', [RoleController::class, 'update']);
			Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
			
			// Status / lookup maintenance
			Route::apiResource('project-statuses', ProjectStatusController::class)->parameters(['project-statuses' => 'status'])->except(['create','edit']);
			Route::apiResource('task-statuses', TaskStatusController::class)->parameters(['task-statuses' => 'status'])->except(['create','edit']);
			Route::apiResource('risk-statuses', RiskIssueStatusController::class)->parameters(['risk-statuses' => 'status'])->except(['create','edit']);
			Route::apiResource('severities', SeverityController::class)->parameters(['severities' => 'severity'])->except(['create','edit']);
			Route::apiResource('priorities', PriorityController::class)->except(['create','edit']);
			Route::apiResource('external-sources', ExternalSourceController::class)->parameters(['external-sources' => 'source'])->except(['create','edit']);
			Route::apiResource('risk-issue-types', RiskIssueTypeController::class)->parameters(['risk-issue-types' => 'type'])->except(['create','edit']);
		});
	});
