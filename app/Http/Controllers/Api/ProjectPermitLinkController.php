<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\StoreProjectPermitLinkRequest;
	use App\Http\Resources\ProjectPermitLinkResource;
	use App\Models\ExternalPermit;
	use App\Models\Project;
	use App\Models\ProjectPermitLink;
	use App\Models\ProjectTask;
	use App\Support\ApiErrorCode;
	use App\Support\ApiResponse;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Validation\ValidationException;
	use Throwable;
	
	class ProjectPermitLinkController extends Controller
	{
		public function store(
        StoreProjectPermitLinkRequest $request,
        Project $project
		) {
			$data = $request->validated();
			
			$permit = ExternalPermit::findOrFail(
            $data['permit_id']
			);
			
			if ($permit->is_source_deleted) {
				throw ValidationException::withMessages([
                'permit_id' => [
				'A deleted ePTW permit cannot be linked.',
                ],
				]);
			}
			
			/*
				* Stage 1 rule:
				* one permit may only belong to one Hospital Tracker project.
			*/
			$linkedToAnotherProject = ProjectPermitLink::query()
            ->where('permit_id', $permit->id)
            ->where('project_id', '!=', $project->id)
            ->where('is_active', true)
            ->exists();
			
			if ($linkedToAnotherProject) {
				return ApiResponse::error(
                ApiErrorCode::EPTW_PERMIT_LINKED_TO_ANOTHER_PROJECT,
                'The permit is already linked to another project.',
                409
				);
			}
			
			$taskIds = array_values(
            array_unique(
			array_map(
			'intval',
			$data['task_ids'] ?? []
			)
            )
			);
			
			if (!empty($taskIds)) {
				$validTaskIds = ProjectTask::query()
                ->where('project_id', $project->id)
                ->whereIn('id', $taskIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
				
				sort($taskIds);
				sort($validTaskIds);
				
				if ($taskIds !== $validTaskIds) {
					throw ValidationException::withMessages([
                    'task_ids' => [
					'All selected tasks must belong to this project.',
                    ],
					]);
				}
			}
			
			try {
				$links = DB::transaction(function () use (
                $request,
                $data,
                $permit,
                $project,
                $taskIds
				) {
					$savedLinks = collect();
					
					/*
						* No tasks selected means project-level permit link.
					*/
					if (empty($taskIds)) {
						$link = ProjectPermitLink::query()
                        ->where('permit_id', $permit->id)
                        ->where('project_id', $project->id)
                        ->whereNull('task_id')
                        ->first();
						
						if ($link) {
							$link->update([
                            'linked_by_user_id' => $request->user()->id,
                            'linked_at' => now(),
                            'notes' => $data['notes'] ?? null,
                            'is_active' => true,
							]);
							} else {
							$link = ProjectPermitLink::create([
                            'permit_id' => (int) $permit->id,
                            'project_id' => (int) $project->id,
                            'task_id' => null,
							
                            'linked_by_user_id' => $request->user()->id,
                            'linked_at' => now(),
                            'notes' => $data['notes'] ?? null,
                            'is_active' => true,
							]);
						}
						
						$savedLinks->push($link);
						} else {
						foreach ($taskIds as $taskId) {
							$link = ProjectPermitLink::query()
                            ->where('permit_id', $permit->id)
                            ->where('project_id', $project->id)
                            ->where('task_id', $taskId)
                            ->first();
							
							if ($link) {
								$link->update([
                                'linked_by_user_id' => $request->user()->id,
                                'linked_at' => now(),
                                'notes' => $data['notes'] ?? null,
                                'is_active' => true,
								]);
								} else {
								$link = ProjectPermitLink::create([
                                'permit_id' => (int) $permit->id,
                                'project_id' => (int) $project->id,
                                'task_id' => (int) $taskId,
								
                                'linked_by_user_id' => $request->user()->id,
                                'linked_at' => now(),
                                'notes' => $data['notes'] ?? null,
                                'is_active' => true,
								]);
							}
							
							$savedLinks->push($link);
						}
					}
					
					\App\Support\Audit::log(
                    $request->user()->id,
                    'PROJECT_PERMIT_LINK',
                    (int) $permit->id,
                    'LINK',
                    [
					'permit_id' => (int) $permit->id,
					'external_form_id' => $permit->external_form_id,
					'project_id' => (int) $project->id,
					'task_ids' => $taskIds,
					'project_level' => empty($taskIds),
					'notes' => $data['notes'] ?? null,
                    ]
					);
					
					return $savedLinks;
				});
				
				$links->each(function ($link) {
					$link->load([
                    'permit',
                    'project:id,code,name',
                    'task:id,project_id,milestone_id,name',
                    'linkedBy:id,name,email',
					]);
				});
				
				return ProjectPermitLinkResource::collection($links)
                ->response()
                ->setStatusCode(201);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::EPTW_PERMIT_LINK_CREATE_FAILED,
                'Failed to link the permit to the project.',
                500
				);
			}
		}
		
		public function destroy(
        Project $project,
        ProjectPermitLink $link
		) {
			if ((int) $link->project_id !== (int) $project->id) {
				abort(404);
			}
			
			if (!$link->is_active) {
				return response()->json([
                'ok' => true,
                'message' => 'Permit link is already inactive.',
				]);
			}
			
			try {
				$snapshot = [
                'permit_id' => (int) $link->permit_id,
                'project_id' => (int) $link->project_id,
                'task_id' => $link->task_id !== null
				? (int) $link->task_id
				: null,
                'notes' => $link->notes,
				];
				
				$link->update([
                'is_active' => false,
				]);
				
				\App\Support\Audit::log(
                request()->user()->id,
                'PROJECT_PERMIT_LINK',
                (int) $link->id,
                'UNLINK',
                [
				'mode' => 'SOFT',
				'snapshot' => $snapshot,
				'changes' => [
				'is_active' => [
				'from' => 1,
				'to' => 0,
				],
				],
                ]
				);
				
				return response()->json([
                'ok' => true,
                'mode' => 'SOFT',
				]);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::EPTW_PERMIT_LINK_DELETE_FAILED,
                'Failed to unlink the permit.',
                500
				);
			}
		}
	}	