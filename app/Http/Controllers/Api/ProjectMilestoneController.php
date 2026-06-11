<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\StoreProjectMilestoneRequest;
	use App\Http\Requests\UpdateProjectMilestoneRequest;
	use App\Http\Resources\ProjectMilestoneResources;
	use App\Models\Project;
	use App\Models\ProjectMilestone;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	
	class ProjectMilestoneController extends Controller
	{
		public function index(Request $request, Project $project)
		{
			$q = $project->milestones()->select([
            'id','project_id','name','milestone_date','status','created_at','updated_at'
			]);
			
			if ($request->filled('status')) {
				$q->where('status', strtoupper(trim($request->status)));
			}
			
			if ($request->filled('search')) {
				$s = trim($request->search);
				$q->where('name', 'like', "%{$s}%");
			}
			
			if ($request->filled('date_from')) {
				$q->whereDate('milestone_date', '>=', $request->date_from);
			}
			if ($request->filled('date_to')) {
				$q->whereDate('milestone_date', '<=', $request->date_to);
			}
			
			$perPage = max(1, min((int)$request->get('per_page', 50), 100));
			
			$p = $q->orderBy('milestone_date')->orderBy('id')->paginate($perPage);
			
			$milestoneIds = $p->getCollection()->pluck('id')->map(fn($v) => (int)$v)->all();
			$budgetByMilestone = $this->budgetSumsForMilestonesViaTasks((int)$project->id, $milestoneIds);
			
			$p->getCollection()->each(function ($m) use ($budgetByMilestone) {
				$m->setAttribute('budget', $budgetByMilestone[(int)$m->id] ?? $this->emptyBudget());
			});
			
			return ProjectMilestoneResources::collection($p);
		}
		
		public function show(Project $project, ProjectMilestone $milestone)
		{
			// If you enable route scoped bindings, milestone is guaranteed to belong to project.
			// Otherwise add a safety check:
			if ((int)$milestone->project_id !== (int)$project->id) {
				abort(404);
			}
			
			$budgetByMilestone = $this->budgetSumsForMilestonesViaTasks((int)$project->id, [(int)$milestone->id]);
			$milestone->setAttribute('budget', $budgetByMilestone[(int)$milestone->id] ?? $this->emptyBudget());
			
			return new ProjectMilestoneResources($milestone);
		}
		
		public function store(StoreProjectMilestoneRequest $request, Project $project)
		{
			$data = $request->validated();
			
			$milestone = $project->milestones()->create([
            'name' => $data['name'],
            'milestone_date' => $data['milestone_date'],
            'status' => $data['status'] ?? 'PENDING',
			]);
			
			\App\Support\Audit::log(
            $request->user()->id,
            'PROJECT_MILESTONE',
            (int)$milestone->id,
            'CREATE',
            [
			'project_id' => (int)$project->id,
			'name' => $milestone->name,
			'milestone_date' => $milestone->milestone_date?->toDateString(),
			'status' => $milestone->status,
            ]
			);
			
			return (new ProjectMilestoneResources($milestone))
            ->response()
            ->setStatusCode(201);
		}
		
		public function update(UpdateProjectMilestoneRequest $request, Project $project, ProjectMilestone $milestone)
		{
			if ((int)$milestone->project_id !== (int)$project->id) {
				abort(404);
			}
			
			$data = $request->validated();
			if (empty($data)) {
				return response()->json(['ok' => true, 'message' => 'No changes']);
			}
			
			$old = $milestone->getOriginal();
			$changes = \App\Support\AuditDiff::diff($old, $data);
			
			if (empty($changes)) {
				return response()->json(['ok' => true, 'message' => 'No changes']);
			}
			
			$milestone->fill($data);
			$milestone->save();
			
			\App\Support\Audit::log(
            $request->user()->id,
            'PROJECT_MILESTONE',
            (int)$milestone->id,
            'UPDATE',
            [
			'project_id' => (int)$project->id,
			'changes' => $changes,
            ]
			);
			
			return new ProjectMilestoneResources($milestone);
		}
		
		public function destroy(Request $request, Project $project, ProjectMilestone $milestone)
		{
			if ((int)$milestone->project_id !== (int)$project->id) {
				abort(404);
			}
			
			$snapshot = [
            'name' => $milestone->name,
            'milestone_date' => $milestone->milestone_date?->toDateString(),
            'status' => $milestone->status,
			];
			
			$milestone->delete();
			
			\App\Support\Audit::log(
            $request->user()->id,
            'PROJECT_MILESTONE',
            (int)$milestone->id,
            'DELETE',
            [
			'mode' => 'HARD',
			'project_id' => (int)$project->id,
			'snapshot' => $snapshot,
            ]
			);
			
			return response()->json(['ok' => true, 'mode' => 'HARD']);
		}
		
		private function emptyBudget(): array
		{
			return [
			'planned_cost' => 0.0,
			'actual_cost' => 0.0,
			'committed_cost' => 0.0,
			'spent_cost' => 0.0,
			'planned_funding' => 0.0,
			'actual_funding' => 0.0,
			'committed_funding' => 0.0,
			'received_funding' => 0.0,
			'net_spent' => 0.0,
			];
		}
		
		/**
			* Milestone budget = sum of allocations on tasks linked to this milestone.
			* (works even if you never allocate directly to milestone_id)
		*/
		private function budgetSumsForMilestonesViaTasks(int $projectId, array $milestoneIds): array
		{
			if (empty($milestoneIds)) return [];
			
			$rows = DB::table('dt_project_tasks as t')
			->join('dt_project_budget_allocations as a', 'a.task_id', '=', 't.id')
			->join('dt_project_budget_lines as l', 'l.id', '=', 'a.budget_line_id')
			->where('t.project_id', $projectId)
			->whereIn('t.milestone_id', $milestoneIds)
			->whereNotNull('t.milestone_id')
			->selectRaw('t.milestone_id as milestone_id')
			->selectRaw("SUM(CASE WHEN l.line_type='COST' THEN a.planned_amount ELSE 0 END) as planned_cost")
			->selectRaw("SUM(CASE WHEN l.line_type='COST' THEN a.actual_amount ELSE 0 END) as actual_cost")
			->selectRaw("SUM(CASE WHEN l.line_type='COST' THEN a.committed_amount ELSE 0 END) as committed_cost")
			->selectRaw("SUM(CASE WHEN l.line_type='FUNDING' THEN a.planned_amount ELSE 0 END) as planned_funding")
			->selectRaw("SUM(CASE WHEN l.line_type='FUNDING' THEN a.actual_amount ELSE 0 END) as actual_funding")
			->selectRaw("SUM(CASE WHEN l.line_type='FUNDING' THEN a.committed_amount ELSE 0 END) as committed_funding")
			->groupBy('t.milestone_id')
			->get();
			
			$out = [];
			foreach ($rows as $r) {
				$actualCost = (float)$r->actual_cost;
				$commCost = (float)$r->committed_cost;
				$actualFund = (float)$r->actual_funding;
				$commFund = (float)$r->committed_funding;
				
				$spentCost = $actualCost + $commCost;
				$recvFund = $actualFund + $commFund;
				
				$out[(int)$r->milestone_id] = [
				'planned_cost' => (float)$r->planned_cost,
				'actual_cost' => $actualCost,
				'committed_cost' => $commCost,
				'spent_cost' => $spentCost,
				'planned_funding' => (float)$r->planned_funding,
				'actual_funding' => $actualFund,
				'committed_funding' => $commFund,
				'received_funding' => $recvFund,
				'net_spent' => $recvFund - $spentCost,
				];
			}
			
			return $out;
		}
	}
