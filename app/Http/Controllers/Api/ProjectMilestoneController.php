<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\StoreProjectMilestoneRequest;
	use App\Http\Requests\UpdateProjectMilestoneRequest;
	use App\Http\Resources\ProjectMilestoneResources;
	use App\Models\Project;
	use App\Models\ProjectMilestone;
	use Illuminate\Http\Request;
	
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
			
			return ProjectMilestoneResources::collection($p);
		}
		
		public function show(Project $project, ProjectMilestone $milestone)
		{
			// If you enable route scoped bindings, milestone is guaranteed to belong to project.
			// Otherwise add a safety check:
			if ((int)$milestone->project_id !== (int)$project->id) {
				abort(404);
			}
			
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
	}
