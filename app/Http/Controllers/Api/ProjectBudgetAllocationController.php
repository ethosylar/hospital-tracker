<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\StoreProjectBudgetAllocationRequest;
	use App\Http\Requests\UpdateProjectBudgetAllocationRequest;
	use App\Http\Resources\ProjectBudgetAllocationResource;
	use App\Models\Project;
	use App\Models\ProjectBudgetAllocation;
	use App\Models\ProjectBudgetLine;
	use App\Models\ProjectTask;
	use App\Models\ProjectMilestone;
	use Illuminate\Http\Request;
	
	class ProjectBudgetAllocationController extends Controller
	{
		public function index(Request $request, Project $project)
		{
			$q = ProjectBudgetAllocation::query()
            ->where('project_id', $project->id)
            ->with(['line:id,project_id,line_type,code,name'])
            ->orderBy('sort_order')
            ->orderBy('id');
			
			if ($request->filled('budget_line_id')) $q->where('budget_line_id', (int)$request->budget_line_id);
			if ($request->filled('task_id')) $q->where('task_id', (int)$request->task_id);
			if ($request->filled('milestone_id')) $q->where('milestone_id', (int)$request->milestone_id);
			
			if ($request->filled('is_active')) $q->where('is_active', (int)$request->is_active);
			
			if ($request->filled('line_type')) {
				$lineType = strtoupper(trim($request->line_type));
				$q->whereHas('line', fn($w) => $w->where('line_type', $lineType));
			}
			
			$perPage = max(1, min((int)$request->get('per_page', 50), 100));
			return ProjectBudgetAllocationResource::collection($q->paginate($perPage));
		}
		
		public function show(Project $project, ProjectBudgetAllocation $alloc)
		{
			if ((int)$alloc->project_id !== (int)$project->id) abort(404);
			
			$alloc->load(['line:id,project_id,line_type,code,name']);
			return new ProjectBudgetAllocationResource($alloc);
		}
		
		public function store(StoreProjectBudgetAllocationRequest $request, Project $project)
		{
			$data = $request->validated();
			
			// default values
			$payload = [
            'project_id' => (int)$project->id,
            'budget_line_id' => (int)$data['budget_line_id'],
            'task_id' => $data['task_id'] ?? null,
            'milestone_id' => $data['milestone_id'] ?? null,
            'planned_amount' => $data['planned_amount'] ?? 0,
            'actual_amount' => $data['actual_amount'] ?? 0,
            'committed_amount' => $data['committed_amount'] ?? 0,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => array_key_exists('is_active', $data) ? (bool)$data['is_active'] : true,
            'notes' => $data['notes'] ?? null,
			];
			
			// ✅ important integrity checks (NO over-allocation checks)
			$this->assertLineAndTargetsInProject(
            (int)$project->id,
            (int)$payload['budget_line_id'],
            $payload['task_id'] ? (int)$payload['task_id'] : null,
            $payload['milestone_id'] ? (int)$payload['milestone_id'] : null
			);
			
			$alloc = ProjectBudgetAllocation::create($payload);
			
			\App\Support\Audit::log(
            $request->user()->id,
            'BUDGET_ALLOCATION',
            (int)$alloc->id,
            'CREATE',
            $payload
			);
			
			return (new ProjectBudgetAllocationResource($alloc->load('line:id,project_id,line_type,code,name')))
            ->response()
            ->setStatusCode(201);
		}
		
		public function update(UpdateProjectBudgetAllocationRequest $request, Project $project, ProjectBudgetAllocation $alloc)
		{
			if ((int)$alloc->project_id !== (int)$project->id) abort(404);
			
			$data = $request->validated();
			if (empty($data)) return response()->json(['ok' => true, 'message' => 'No changes']);
			
			// normalize only provided fields
			if (array_key_exists('is_active', $data)) $data['is_active'] = (bool)$data['is_active'];
			if (array_key_exists('planned_amount', $data) && $data['planned_amount'] === null) $data['planned_amount'] = 0;
			if (array_key_exists('actual_amount', $data) && $data['actual_amount'] === null) $data['actual_amount'] = 0;
			if (array_key_exists('committed_amount', $data) && $data['committed_amount'] === null) $data['committed_amount'] = 0;
			if (array_key_exists('sort_order', $data) && $data['sort_order'] === null) $data['sort_order'] = 0;
			
			// validate cross-refs in project (line/task/milestone)
			$candidateLineId = array_key_exists('budget_line_id', $data) ? (int)$data['budget_line_id'] : (int)$alloc->budget_line_id;
			$candidateTaskId = array_key_exists('task_id', $data) ? ($data['task_id'] ? (int)$data['task_id'] : null) : ($alloc->task_id ? (int)$alloc->task_id : null);
			$candidateMilestoneId = array_key_exists('milestone_id', $data) ? ($data['milestone_id'] ? (int)$data['milestone_id'] : null) : ($alloc->milestone_id ? (int)$alloc->milestone_id : null);
			
			$this->assertLineAndTargetsInProject((int)$project->id, $candidateLineId, $candidateTaskId, $candidateMilestoneId);
			
			$old = $alloc->getOriginal();
			$alloc->fill($data);
			
			if (!$alloc->isDirty()) return response()->json(['ok' => true, 'message' => 'No changes']);
			
			$dirty = $alloc->getDirty();
			$alloc->save();
			
			$changes = \App\Support\AuditDiff::diff($old, $dirty);
			
			\App\Support\Audit::log(
            $request->user()->id,
            'BUDGET_ALLOCATION',
            (int)$alloc->id,
            'UPDATE',
            $changes
			);
			
			return new ProjectBudgetAllocationResource($alloc->refresh()->load('line:id,project_id,line_type,code,name'));
		}
		
		public function destroy(Request $request, Project $project, ProjectBudgetAllocation $alloc)
		{
			if ((int)$alloc->project_id !== (int)$project->id) abort(404);
			
			$snapshot = [
            'project_id' => (int)$alloc->project_id,
            'budget_line_id' => (int)$alloc->budget_line_id,
            'task_id' => $alloc->task_id ? (int)$alloc->task_id : null,
            'milestone_id' => $alloc->milestone_id ? (int)$alloc->milestone_id : null,
            'planned_amount' => (float)$alloc->planned_amount,
            'actual_amount' => (float)$alloc->actual_amount,
            'committed_amount' => (float)$alloc->committed_amount,
            'is_active' => (bool)$alloc->is_active,
			];
			
			$id = (int)$alloc->id;
			$alloc->delete();
			
			\App\Support\Audit::log(
            $request->user()->id,
            'BUDGET_ALLOCATION',
            $id,
            'DELETE',
            [
			'mode' => 'HARD',
			'snapshot' => $snapshot,
            ]
			);
			
			return response()->json(['ok' => true, 'mode' => 'HARD']);
		}
		
		/**
			* Ensures:
			* - budget line belongs to project
			* - task belongs to project (if provided)
			* - milestone belongs to project (if provided)
		*/
		private function assertLineAndTargetsInProject(int $projectId, int $budgetLineId, ?int $taskId, ?int $milestoneId): void
		{
			$lineOk = ProjectBudgetLine::query()
            ->whereKey($budgetLineId)
            ->where('project_id', $projectId)
            ->exists();
			
			if (!$lineOk) {
				abort(422, 'budget_line_id must belong to the same project');
			}
			
			if ($taskId !== null) {
				$taskOk = ProjectTask::query()
                ->whereKey($taskId)
                ->where('project_id', $projectId)
                ->exists();
				
				if (!$taskOk) abort(422, 'task_id must belong to the same project');
			}
			
			if ($milestoneId !== null) {
				$msOk = ProjectMilestone::query()
                ->whereKey($milestoneId)
                ->where('project_id', $projectId)
                ->exists();
				
				if (!$msOk) abort(422, 'milestone_id must belong to the same project');
			}
		}
	}	