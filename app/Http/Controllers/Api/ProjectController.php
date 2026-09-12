<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectIndexRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Support\ApiErrorCode;
use App\Support\ApiResponse;
use App\Support\ProjectAccess;
use App\Support\ProjectSiteRules;
use Illuminate\Http\Request;
use Throwable;

class ProjectController extends Controller
{
	public function index(ProjectIndexRequest $request)
	{
		$data = $request->validated();

		$query = Project::query()
			->with([
				'site:id,code,name,short_name,site_type,is_active',
				'department:id,site_id,code,name',
				'status:id,code,name',
				'priority:id,code,name',
				'owner:id,name,email',
				'category:id,code,name',
			]);

		ProjectAccess::applyViewScope(
			$query,
			$request->user(),
			'dt_projects.site_id'
		);

		if (!empty($data['site_id'])) {
			$query->where(
				'dt_projects.site_id',
				(int) $data['site_id']
			);
		}

		if (!empty($data['department_id'])) {
			$query->where(
				'department_id',
				(int) $data['department_id']
			);
		}

		if (!empty($data['status_id'])) {
			$query->where(
				'project_status_id',
				(int) $data['status_id']
			);
		}

		if (!empty($data['priority_id'])) {
			$query->where(
				'priority_id',
				(int) $data['priority_id']
			);
		}

		if (!empty($data['project_category_id'])) {
			$query->where(
				'project_category_id',
				(int) $data['project_category_id']
			);
		}

		if (!empty($data['owner_user_id'])) {
			$query->where(
				'owner_user_id',
				(int) $data['owner_user_id']
			);
		}

		if (!empty($data['search'])) {
			$search = $data['search'];

			$query->where(function ($where) use ($search) {
				$where
					->where(
						'dt_projects.name',
						'like',
						"%{$search}%"
					)
					->orWhere(
						'dt_projects.code',
						'like',
						"%{$search}%"
					);
			});
		}

		if (!empty($data['delayed'])) {
			$query
				->whereNotNull('target_end_date')
				->whereDate(
					'target_end_date',
					'<',
					today()->toDateString()
				)
				->whereHas(
					'status',
					fn($status) => $status->whereNotIn(
						'code',
						['COMPLETED', 'CANCELLED']
					)
				);
		}

		$perPage = (int) ($data['per_page'] ?? 10);

		return ProjectResource::collection(
			$query
				->orderByDesc('dt_projects.updated_at')
				->paginate($perPage)
		);
	}

	public function show(
		Request $request,
		$project
	) {
		$project = ProjectAccess::visibleQuery(
			$request->user()
		)
			->with([
				'site:id,code,name,short_name,site_type,is_active',
				'department:id,site_id,code,name',
				'status:id,code,name',
				'priority:id,code,name',
				'owner:id,name,email',
				'category:id,code,name',
			])
			->find($project);

		if (!$project) {
			return ApiResponse::error(
				ApiErrorCode::PROJECT_NOT_FOUND,
				'Project was not found.',
				[],
				404
			);
		}

		return new ProjectResource($project);
	}

	public function store(
		StoreProjectRequest $request
	) {
		$data = $request->validated();
		$siteId = (int) $data['site_id'];

		if (
			!ProjectAccess::canManageSite(
				$request->user(),
				$siteId
			)
		) {
			return ApiResponse::error(
				ApiErrorCode::PROJECT_SITE_ACCESS_DENIED,
				'You do not have management access to the selected project site.',
				[],
				403
			);
		}

		ProjectSiteRules::validate(
			$siteId,
			isset($data['department_id'])
				? (int) $data['department_id']
				: null,
			isset($data['owner_user_id'])
				? (int) $data['owner_user_id']
				: null
		);

		$payload = [
			...$data,
			'site_id' => $siteId,
			'code' => strtoupper(trim($data['code'])),
			'name' => trim($data['name']),
			'currency_code' => $data['currency_code'] ?? 'MYR',
			'progress' => $data['progress'] ?? 0,
			'planned_progress' => $data['planned_progress'] ?? 0,
			'planned_cost_total' => $data['planned_cost_total'] ?? 0,
			'actual_cost_total' => $data['actual_cost_total'] ?? 0,
			'committed_cost_total' => $data['committed_cost_total'] ?? 0,
			'planned_funding_total' => $data['planned_funding_total'] ?? 0,
			'actual_funding_total' => $data['actual_funding_total'] ?? 0,
		];

		try {
			$project = Project::create($payload);

			\App\Support\Audit::log(
				$request->user()->id,
				'PROJECT',
				(int) $project->id,
				'CREATE',
				$payload
			);

			$project->load([
				'site:id,code,name,short_name,site_type,is_active',
				'department:id,site_id,code,name',
				'status:id,code,name',
				'priority:id,code,name',
				'owner:id,name,email',
				'category:id,code,name',
			]);

			/*
             * Keep top-level id because the current Angular createProject()
             * flow expects response.id.
             */
			return response()->json([
				'id' => (int) $project->id,
				'data' => (
					new ProjectResource($project)
				)->resolve($request),
			], 201);
		} catch (Throwable $e) {
			report($e);

			return ApiResponse::error(
				ApiErrorCode::PROJECT_CREATE_FAILED,
				'Failed to create project.',
				$this->errorDetails($e),
				500
			);
		}
	}

	public function update(
		UpdateProjectRequest $request,
		$project
	) {
		$project = ProjectAccess::visibleQuery(
			$request->user()
		)->find($project);

		if (!$project) {
			return ApiResponse::error(
				ApiErrorCode::PROJECT_NOT_FOUND,
				'Project was not found.',
				[],
				404
			);
		}

		if (
			!ProjectAccess::canManage(
				$request->user(),
				$project
			)
		) {
			return ApiResponse::error(
				ApiErrorCode::PROJECT_SITE_ACCESS_DENIED,
				'You do not have management access to this project site.',
				[],
				403
			);
		}

		$data = $request->validated();

		if (empty($data)) {
			return response()->json([
				'ok' => true,
				'message' => 'No changes.',
			]);
		}

		$candidateSiteId = array_key_exists(
			'site_id',
			$data
		)
			? (int) $data['site_id']
			: (int) $project->site_id;

		if (
			$candidateSiteId !== (int) $project->site_id
			&& !ProjectAccess::canManageSite(
				$request->user(),
				$candidateSiteId
			)
		) {
			return ApiResponse::error(
				ApiErrorCode::PROJECT_SITE_CHANGE_DENIED,
				'You do not have management access to the destination site.',
				[],
				403
			);
		}

		$candidateDepartmentId = array_key_exists(
			'department_id',
			$data
		)
			? (
				$data['department_id'] !== null
				? (int) $data['department_id']
				: null
			)
			: (
				$project->department_id
				? (int) $project->department_id
				: null
			);

		$candidateOwnerId = array_key_exists(
			'owner_user_id',
			$data
		)
			? (
				$data['owner_user_id'] !== null
				? (int) $data['owner_user_id']
				: null
			)
			: (
				$project->owner_user_id
				? (int) $project->owner_user_id
				: null
			);

		ProjectSiteRules::validate(
			$candidateSiteId,
			$candidateDepartmentId,
			$candidateOwnerId
		);

		$old = $project->getOriginal();

		$project->fill($data);

		if (!$project->isDirty()) {
			return response()->json([
				'ok' => true,
				'message' => 'No changes.',
			]);
		}

		$dirty = $project->getDirty();

		try {
			$project->save();

			$changes = \App\Support\AuditDiff::diff(
				$old,
				$dirty
			);

			\App\Support\Audit::log(
				$request->user()->id,
				'PROJECT',
				(int) $project->id,
				'UPDATE',
				$changes
			);

			$project->load([
				'site:id,code,name,short_name,site_type,is_active',
				'department:id,site_id,code,name',
				'status:id,code,name',
				'priority:id,code,name',
				'owner:id,name,email',
				'category:id,code,name',
			]);

			return response()->json([
				'ok' => true,
				'data' => (
					new ProjectResource($project)
				)->resolve($request),
			]);
		} catch (Throwable $e) {
			report($e);

			return ApiResponse::error(
				ApiErrorCode::PROJECT_UPDATE_FAILED,
				'Failed to update project.',
				$this->errorDetails($e),
				500
			);
		}
	}

	public function destroy(
		Request $request,
		$project
	) {
		$project = ProjectAccess::visibleQuery(
			$request->user()
		)->find($project);

		if (!$project) {
			return ApiResponse::error(
				ApiErrorCode::PROJECT_NOT_FOUND,
				'Project was not found.',
				[],
				404
			);
		}

		if (
			!ProjectAccess::canManage(
				$request->user(),
				$project
			)
		) {
			return ApiResponse::error(
				ApiErrorCode::PROJECT_SITE_ACCESS_DENIED,
				'You do not have management access to this project site.',
				[],
				403
			);
		}

		$snapshot = [
			'site_id' => (int) $project->site_id,
			'code' => $project->code,
			'name' => $project->name,
			'project_status_id' => $project->project_status_id,
			'priority_id' => $project->priority_id,
			'department_id' => $project->department_id,
			'owner_user_id' => $project->owner_user_id,
		];

		$id = (int) $project->id;

		try {
			$project->delete();

			\App\Support\Audit::log(
				$request->user()->id,
				'PROJECT',
				$id,
				'DELETE',
				[
					'mode' => 'HARD',
					'snapshot' => $snapshot,
				]
			);

			return response()->json([
				'ok' => true,
				'mode' => 'HARD',
			]);
		} catch (Throwable $e) {
			report($e);

			return ApiResponse::error(
				ApiErrorCode::PROJECT_DELETE_FAILED,
				'Failed to delete project.',
				$this->errorDetails($e),
				500
			);
		}
	}

	private function errorDetails(Throwable $e): array
	{
		if (!config('app.debug')) {
			return [];
		}

		return [
			'exception' => $e->getMessage(),
			'exception_class' => get_class($e),
		];
	}
}
