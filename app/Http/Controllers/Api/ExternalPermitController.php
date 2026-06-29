<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\ExternalPermitIndexRequest;
	use App\Http\Resources\ExternalPermitResource;
	use App\Models\ExternalPermit;
	use App\Models\Project;
	use App\Models\ProjectMilestone;
	use App\Models\ProjectTask;
	
	class ExternalPermitController extends Controller
	{
		public function index(ExternalPermitIndexRequest $request)
		{
			$data = $request->validated();
			
			$q = ExternalPermit::query()
            ->with([
			'source:id,code,name,base_url',
            ])
            ->withCount([
			'links as active_links_count' => function ($w) {
				$w->where('is_active', true);
			},
            ]);
			
			if (empty($data['include_deleted'])) {
				$q->where('is_source_deleted', false);
			}
			
			if (!empty($data['normalized_status'])) {
				$q->where(
                'normalized_status',
                $data['normalized_status']
				);
			}
			
			if (!empty($data['raw_status'])) {
				$q->where(
                'raw_status',
                $data['raw_status']
				);
			}
			
			if (!empty($data['company_name'])) {
				$q->where(
                'company_name',
                'like',
                '%' . $data['company_name'] . '%'
				);
			}
			
			if (!empty($data['service_name'])) {
				$q->where(
                'service_name',
                'like',
                '%' . $data['service_name'] . '%'
				);
			}
			
			/*
				* Find permits overlapping the requested date range.
			*/
			if (!empty($data['date_from'])) {
				$q->where(function ($w) use ($data) {
					$w->whereNull('work_end_date')
                    ->orWhereDate(
					'work_end_date',
					'>=',
					$data['date_from']
                    );
				});
			}
			
			if (!empty($data['date_to'])) {
				$q->where(function ($w) use ($data) {
					$w->whereNull('work_start_date')
                    ->orWhereDate(
					'work_start_date',
					'<=',
					$data['date_to']
                    );
				});
			}
			
			if (!empty($data['project_id'])) {
				$projectId = (int) $data['project_id'];
				
				$q->whereHas('links', function ($w) use ($projectId) {
					$w->where('project_id', $projectId)
                    ->where('is_active', true);
				});
			}
			
			if (!empty($data['task_id'])) {
				$taskId = (int) $data['task_id'];
				
				$q->whereHas('links', function ($w) use ($taskId) {
					$w->where('task_id', $taskId)
                    ->where('is_active', true);
				});
			}
			
			if (array_key_exists('is_linked', $data)) {
				if ((bool) $data['is_linked']) {
					$q->whereHas('links', function ($w) {
						$w->where('is_active', true);
					});
					} else {
					$q->whereDoesntHave('links', function ($w) {
						$w->where('is_active', true);
					});
				}
			}
			
			if (!empty($data['search'])) {
				$search = trim($data['search']);
				
				$q->where(function ($w) use ($search) {
					$w->where(
                    'external_form_id',
                    'like',
                    "%{$search}%"
					)
                    ->orWhere(
					'external_permit_id',
					'like',
					"%{$search}%"
                    )
                    ->orWhere(
					'applicant_name',
					'like',
					"%{$search}%"
                    )
                    ->orWhere(
					'company_name',
					'like',
					"%{$search}%"
                    )
                    ->orWhere(
					'supervisor_name',
					'like',
					"%{$search}%"
                    )
                    ->orWhere(
					'exact_location',
					'like',
					"%{$search}%"
                    )
                    ->orWhere(
					'work_type',
					'like',
					"%{$search}%"
                    );
				});
			}
			
			$perPage = (int) ($data['per_page'] ?? 50);
			
			return ExternalPermitResource::collection(
            $q->orderByDesc('work_start_date')
			->orderByDesc('id')
			->paginate($perPage)
			);
		}
		
		public function show(ExternalPermit $permit)
		{
			$permit->load([
            'source:id,code,name,base_url',
			
            'links' => function ($q) {
                $q->where('is_active', true)
				->orderBy('linked_at');
			},
			
            'links.project:id,code,name',
            'links.task:id,project_id,milestone_id,name',
            'links.linkedBy:id,name,email',
			]);
			
			return new ExternalPermitResource($permit);
		}
		
		public function projectIndex(Project $project)
		{
			$permits = ExternalPermit::query()
            ->where('is_source_deleted', false)
            ->whereHas('links', function ($q) use ($project) {
                $q->where('project_id', $project->id)
				->where('is_active', true);
			})
            ->with([
			'source:id,code,name,base_url',
			
			'links' => function ($q) use ($project) {
				$q->where('project_id', $project->id)
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
			$permits = ExternalPermit::query()
            ->where('is_source_deleted', false)
            ->whereHas('links', function ($q) use ($task) {
                $q->where('task_id', $task->id)
				->where('is_active', true);
			})
            ->with([
			'source:id,code,name,base_url',
			
			'links' => function ($q) use ($task) {
				$q->where('task_id', $task->id)
				->where('is_active', true);
			},
			
			'links.project:id,code,name',
			'links.task:id,project_id,milestone_id,name',
			'links.linkedBy:id,name,email',
            ])
            ->orderByDesc('work_start_date')
            ->get();
			
			return ExternalPermitResource::collection($permits);
		}
		
		public function milestoneIndex(
        Project $project,
        ProjectMilestone $milestone
		) {
			if ((int) $milestone->project_id !== (int) $project->id) {
				abort(404);
			}
			
			/*
				* Milestone permits are derived from task permit links.
			*/
			$permits = ExternalPermit::query()
            ->where('is_source_deleted', false)
            ->whereHas('links.task', function ($q) use (
			$project,
			$milestone
            ) {
                $q->where(
				'project_id',
				$project->id
                )
				->where(
				'milestone_id',
				$milestone->id
				);
			})
            ->whereHas('links', function ($q) {
                $q->where('is_active', true);
			})
            ->with([
			'source:id,code,name,base_url',
			
			'links' => function ($q) use (
			$project,
			$milestone
			) {
				$q->where(
				'project_id',
				$project->id
				)
				->where('is_active', true)
				->whereHas('task', function ($taskQuery) use (
				$milestone
				) {
					$taskQuery->where(
					'milestone_id',
					$milestone->id
					);
				});
			},
			
			'links.task:id,project_id,milestone_id,name',
			'links.linkedBy:id,name,email',
            ])
            ->orderByDesc('work_start_date')
            ->get();
			
			return ExternalPermitResource::collection($permits);
		}
	}	