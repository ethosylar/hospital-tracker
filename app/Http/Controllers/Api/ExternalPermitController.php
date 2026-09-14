<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExternalPermitIndexRequest;
use App\Http\Resources\ExternalPermitResource;
use App\Models\ExternalPermit;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Support\ExternalPermitAccess;

class ExternalPermitController extends Controller
{
	public function index(ExternalPermitIndexRequest $request)
	{
		$data = $request->validated();

		$query = ExternalPermitAccess::visibleQuery($request->user())
			->with([
				'site:id,code,name,short_name',
				'source:id,site_id,code,name,base_url',
			])
			->withCount([
				'links as active_links_count' =>
				function ($where) {
					$where->where('is_active', true);
				},
			]);

		if (!empty($data['site_id'])) {
			$query->where('dt_external_permits.site_id', (int) $data['site_id']);
		}

		if (empty($data['include_deleted'])) {
			$query->where('is_source_deleted', false);
		}

		if (!empty($data['normalized_status'])) {
			$query->where('normalized_status', $data['normalized_status']);
		}

		if (!empty($data['raw_status'])) {
			$query->where('raw_status', $data['raw_status']);
		}

		if (!empty($data['company_name'])) {
			$query->where('company_name', 'like', '%' . $data['company_name'] . '%');
		}

		if (!empty($data['service_name'])) {
			$query->where('service_name', 'like', '%' . $data['service_name'] . '%');
		}

		if (!empty($data['date_from'])) {
			$query->where(
				function ($where) use ($data) {
					$where
						->whereNull('work_end_date')
						->orWhereDate('work_end_date', '>=', $data['date_from']);
				}
			);
		}

		if (!empty($data['date_to'])) {
			$query->where(
				function ($where) use ($data) {
					$where
						->whereNull('work_start_date')
						->orWhereDate('work_start_date', '<=', $data['date_to']);
				}
			);
		}

		if (!empty($data['project_id'])) {
			$projectId = (int) $data['project_id'];

			$query->whereHas(
				'links',
				function ($where) use ($projectId) {
					$where
						->where('project_id', $projectId)
						->where('is_active', true);
				}
			);
		}

		if (!empty($data['task_id'])) {
			$taskId = (int) $data['task_id'];
			$query->whereHas(
				'links',
				function ($where) use ($taskId) {
					$where
						->where('task_id', $taskId)
						->where('is_active', true);
				}
			);
		}

		if (array_key_exists('is_linked', $data)) {
			if ((bool) $data['is_linked']) {
				$query->whereHas(
					'links',
					fn($where) => $where->where('is_active', true)
				);
			} else {
				$query->whereDoesntHave(
					'links',
					fn($where) => $where->where('is_active', true)
				);
			}
		}

		if (!empty($data['search'])) {
			$search = trim($data['search']);

			$query->where(
				function ($where) use ($search) {
					$where
						->where('external_form_id', 'like', "%{$search}%")
						->orWhere('external_permit_id', 'like', "%{$search}%")
						->orWhere('applicant_name', 'like', "%{$search}%")
						->orWhere('company_name', 'like', "%{$search}%")
						->orWhere('supervisor_name', 'like', "%{$search}%")
						->orWhere('exact_location', 'like', "%{$search}%")
						->orWhere('work_type', 'like', "%{$search}%");
				}
			);
		}

		$perPage = (int) ($data['per_page'] ?? 50);

		return ExternalPermitResource::collection(
			$query
				->orderByDesc('work_start_date')
				->orderByDesc('id')
				->paginate($perPage)
		);
	}

	public function show(ExternalPermit $permit)
	{
		$permit->load([
			'site:id,code,name,short_name',
			'source:id,site_id,code,name,base_url',
			'links' => function ($query) {
				$query
					->where('is_active', true)
					->orderBy('linked_at');
			},

			'links.project:id,site_id,code,name',
			'links.task:id,project_id,milestone_id,name',
			'links.linkedBy:id,name,email',
		]);

		return new ExternalPermitResource($permit);
	}

	public function projectIndex(Project $project)
	{
		$permits = ExternalPermit::query()
			->where('site_id', $project->site_id)
			->where('is_source_deleted', false)
			->whereHas(
				'links',
				function ($query) use ($project) {
					$query
						->where('project_id', $project->id)
						->where('is_active', true);
				}
			)
			->with([
				'site:id,code,name,short_name',
				'source:id,site_id,code,name,base_url',
				'links' =>
				function ($query) use ($project) {
					$query
						->where('project_id', $project->id)
						->where('is_active', true);
				},
				'links.task:id,project_id,milestone_id,name',
				'links.linkedBy:id,name,email',
			])
			->orderByDesc('work_start_date')
			->get();

		return ExternalPermitResource::collection($permits);
	}

	public function taskIndex(ProjectTask $task)
	{
		$project = Project::query()->findOrFail($task->project_id);

		$permits = ExternalPermit::query()
			->where('site_id', $project->site_id)
			->where('is_source_deleted', false)
			->whereHas(
				'links',
				function ($query) use ($task) {
					$query
						->where('task_id', $task->id)
						->where('is_active', true);
				}
			)
			->with([
				'site:id,code,name,short_name',
				'source:id,site_id,code,name,base_url',
				'links' =>
				function ($query) use ($task) {
					$query
						->where('task_id', $task->id)
						->where('is_active', true);
				},
				'links.project:id,site_id,code,name',
				'links.task:id,project_id,milestone_id,name',
				'links.linkedBy:id,name,email',
			])
			->orderByDesc('work_start_date')
			->get();

		return ExternalPermitResource::collection($permits);
	}

	public function milestoneIndex(Project $project, ProjectMilestone $milestone)
	{
		if ((int) $milestone->project_id !== (int) $project->id) {
			abort(404);
		}

		$permits = ExternalPermit::query()
			->where('site_id', $project->site_id)
			->where('is_source_deleted', false)
			->whereHas(
				'links.task',
				function ($query) use ($project, $milestone) {
					$query
						->where('project_id', $project->id)
						->where('milestone_id', $milestone->id);
				}
			)
			->whereHas(
				'links',
				fn($query) =>
				$query->where('is_active', true)
			)
			->with([
				'site:id,code,name,short_name',
				'source:id,site_id,code,name,base_url',
				'links' => function ($query) use ($project, $milestone) {
					$query
						->where('project_id', $project->id)
						->where('is_active', true)
						->whereHas(
							'task',
							function ($taskQuery) use ($milestone) {
								$taskQuery
									->where('milestone_id', $milestone->id);
							}
						);
				},
				'links.task:id,project_id,milestone_id,name',
				'links.linkedBy:id,name,email',
			])
			->orderByDesc('work_start_date')
			->get();

		return ExternalPermitResource::collection($permits);
	}
}
