<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\StoreProjectTaskRequest;
	use App\Http\Requests\UpdateProjectTaskRequest;
	use App\Http\Resources\ProjectTaskGanttResource;
	use App\Models\Project;
	use App\Models\ProjectTask;
	use App\Models\ProjectMilestone;
	use Illuminate\Http\Request;
	use Illuminate\Validation\ValidationException;
	
	class ProjectTaskController extends Controller
	{
		public function gantt($project)
		{
			// Let your global handler format 404
			Project::query()->whereKey($project)->firstOrFail();
			
			$tasks = ProjectTask::query()
            ->where('project_id', $project)
            ->with([
			'status:id,code,name',
			'actualStatus:id,code,name',
			'assignedTo:id,name',
			'milestone:id,project_id,name,milestone_date',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
			
			return response()->json([
            'project_id' => (int)$project,
            'tasks' => ProjectTaskGanttResource::collection($tasks),
			]);
		}
		
		public function store(StoreProjectTaskRequest $request, $project)
		{
			Project::query()->whereKey($project)->firstOrFail();
			
			$data = $request->validated();
			
			// Ensure parent/depends belong to same project
			$this->assertMilestoneBelongsToProject((int)$project, $data);
			$this->assertSameProjectLinks((int)$project, $data, null);
			
			// normalize
			if (array_key_exists('name', $data)) {
				$data['name'] = trim((string)$data['name']);
			}
			if (array_key_exists('task_color', $data) && $data['task_color'] !== null) {
				$c = strtoupper(trim((string)$data['task_color']));
				if ($c !== '' && $c[0] !== '#') $c = '#'.$c;
				$data['task_color'] = $c;
			}
			
			$payload = [
            ...$data,
            'project_id' => (int)$project,
            'progress' => $data['progress'] ?? 0,
            'sort_order' => $data['sort_order'] ?? 0,
            'duration' => $data['duration'] ?? $this->calcDuration($data['start_date'] ?? null, $data['end_date'] ?? null),
			];
			
			$task = ProjectTask::create($payload);
			
			\App\Support\Audit::log(
            $request->user()->id,
            'TASK',
            (int)$task->id,
            'CREATE',
            [
			'project_id' => (int)$project,
			'name' => $payload['name'] ?? null,
			'task_status_id' => $payload['task_status_id'] ?? null,
			'actual_task_status_id' => $payload['actual_task_status_id'] ?? null,
			'progress' => $payload['progress'] ?? null,
			'start_date' => $payload['start_date'] ?? null,
			'end_date' => $payload['end_date'] ?? null,
			'actual_start_date' => $payload['actual_start_date'] ?? null,
			'actual_end_date' => $payload['actual_end_date'] ?? null,
			'duration' => $payload['duration'] ?? null,
			'task_color' => $payload['task_color'] ?? null,
			'assigned_to_user_id' => $payload['assigned_to_user_id'] ?? null,
			'sort_order' => $payload['sort_order'] ?? null,
			'parent_task_id' => $payload['parent_task_id'] ?? null,
			'depends_on_task_id' => $payload['depends_on_task_id'] ?? null,
            ]
			);
			
			return response()->json(['id' => $task->id], 201);
		}
		
		public function update(UpdateProjectTaskRequest $request, $task)
		{
			$t = ProjectTask::findOrFail($task);
			
			$data = $request->validated();
			if (empty($data)) {
				return response()->json(['ok' => true, 'message' => 'No changes']);
			}
			
			// Ensure parent/depends belong to same project as this task
			$this->assertMilestoneBelongsToProject((int)$t->project_id, $data);
			$this->assertSameProjectLinks((int)$t->project_id, $data, (int)$t->id);
			
			// normalize
			if (array_key_exists('name', $data)) {
				$data['name'] = trim((string)$data['name']);
			}
			if (array_key_exists('task_color', $data)) {
				if ($data['task_color'] === null || trim((string)$data['task_color']) === '') {
					$data['task_color'] = null;
					} else {
					$c = strtoupper(trim((string)$data['task_color']));
					if ($c[0] !== '#') $c = '#'.$c;
					$data['task_color'] = $c;
				}
			}
			
			// auto duration if not explicitly set AND dates changed
			if (!array_key_exists('duration', $data) && (array_key_exists('start_date', $data) || array_key_exists('end_date', $data))) {
				$nextStart = array_key_exists('start_date', $data)
                ? $data['start_date']
                : ($t->start_date?->format('Y-m-d'));
				
				$nextEnd = array_key_exists('end_date', $data)
                ? $data['end_date']
                : ($t->end_date?->format('Y-m-d'));
				
				$data['duration'] = $this->calcDuration($nextStart, $nextEnd);
			}
			
			$old = $t->getOriginal();
			
			$t->fill($data);
			
			if (!$t->isDirty()) {
				return response()->json(['ok' => true, 'message' => 'No changes']);
			}
			
			$dirty = $t->getDirty();
			$t->save();
			
			$changes = \App\Support\AuditDiff::diff($old, $dirty);
			
			\App\Support\Audit::log(
            $request->user()->id,
            'TASK',
            (int)$t->id,
            'UPDATE',
            $changes
			);
			
			return response()->json(['ok' => true]);
		}
		
		public function destroy(Request $request, $task)
		{
			$t = ProjectTask::findOrFail($task);
			
			$snapshot = [
            'project_id' => $t->project_id,
            'name' => $t->name,
			
            'task_status_id' => $t->task_status_id,
            'actual_task_status_id' => $t->actual_task_status_id,
			
            'progress' => $t->progress,
			
            'start_date' => $t->start_date?->format('Y-m-d'),
            'end_date' => $t->end_date?->format('Y-m-d'),
			
            'actual_start_date' => $t->actual_start_date?->format('Y-m-d'),
            'actual_end_date' => $t->actual_end_date?->format('Y-m-d'),
			
            'duration' => $t->duration,
            'task_color' => $t->task_color,
			
            'assigned_to_user_id' => $t->assigned_to_user_id,
            'parent_task_id' => $t->parent_task_id,
            'depends_on_task_id' => $t->depends_on_task_id,
            'sort_order' => $t->sort_order,
			];
			
			$t->delete();
			
			\App\Support\Audit::log(
            $request->user()->id,
            'TASK',
            (int)$task,
            'DELETE',
            [
			'mode' => 'HARD',
			'snapshot' => $snapshot,
            ]
			);
			
			return response()->json(['ok' => true, 'mode' => 'HARD']);
		}
		
		/**
			* Centralised “same project” safety without try/catch.
			* Throws ValidationException => your global handler returns consistent 422 error.
		*/
		private function assertSameProjectLinks(int $projectId, array $data, ?int $selfId): void
		{
			foreach (['parent_task_id', 'depends_on_task_id'] as $fk) {
				if (!array_key_exists($fk, $data) || empty($data[$fk])) continue;
				
				$targetId = (int)$data[$fk];
				
				// cannot link to itself (update case)
				if ($selfId !== null && $targetId === $selfId) {
					throw ValidationException::withMessages([
                    $fk => ["{$fk} cannot reference itself"],
					]);
				}
				
				$ok = ProjectTask::query()
                ->whereKey($targetId)
                ->where('project_id', $projectId)
                ->exists();
				
				if (!$ok) {
					throw ValidationException::withMessages([
                    $fk => ["{$fk} must belong to the same project"],
					]);
				}
			}
		}
		
		private function assertMilestoneBelongsToProject(int $projectId, array $data): void
		{
			if (!array_key_exists('milestone_id', $data) || empty($data['milestone_id'])) return;
			
			$mid = (int)$data['milestone_id'];
			
			$ok = ProjectMilestone::query()
			->whereKey($mid)
			->where('project_id', $projectId)
			->exists();
			
			if (!$ok) {
				throw ValidationException::withMessages([
				'milestone_id' => ['milestone_id must belong to the same project'],
				]);
			}
		}
		
		private function calcDuration(?string $start, ?string $end): int
		{
			if (!$start || !$end) return 0;
			
			$s = \Carbon\Carbon::parse($start)->startOfDay();
			$e = \Carbon\Carbon::parse($end)->startOfDay();
			
			if ($e->lt($s)) return 0;
			
			// inclusive days (16 -> 27 = 12 days)
			return $s->diffInDays($e) + 1;
		}
	}
