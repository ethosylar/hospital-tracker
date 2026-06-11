<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\StoreProjectRequest;
	use App\Http\Requests\UpdateProjectRequest;
	use App\Http\Resources\ProjectResource;
	use App\Models\Project;
	use Illuminate\Http\Request;
	
	class ProjectController extends Controller
	{
		public function index(Request $request)
		{
			$q = Project::query()
            ->with([
			'department:id,code,name',
			'status:id,code,name',
			'priority:id,code,name',
			'owner:id,name,email',
			'category:id,code,name',
            ]);
			
			// Filters
			if ($request->filled('department_id')) $q->where('department_id', $request->department_id);
			if ($request->filled('status_id')) $q->where('project_status_id', $request->status_id);
			if ($request->filled('priority_id')) $q->where('priority_id', $request->priority_id);
			if ($request->filled('project_category_id')) $q->where('project_category_id', (int)$request->project_category_id);
			
			if ($request->filled('search')) {
				$s = trim($request->search);
				$q->where(function ($w) use ($s) {
					$w->where('name', 'like', "%{$s}%")
					->orWhere('code', 'like', "%{$s}%");
				});
			}
			
			// Delayed filter (target_end_date < today AND status not completed/cancelled)
			if ($request->boolean('delayed')) {
				$q->whereNotNull('target_end_date')
				->whereDate('target_end_date', '<', now()->toDateString())
				->whereHas('status', function ($st) {
					$st->whereNotIn('code', ['COMPLETED', 'CANCELLED']);
				});
			}
			
			$perPage = max(1, min((int)$request->get('per_page', 10), 100));
			
			return ProjectResource::collection(
            $q->orderByDesc('updated_at')->paginate($perPage)
			);
		}
		
		public function show($project)
		{
			$p = Project::query()
            ->with([
			'department:id,code,name',
			'status:id,code,name',
			'priority:id,code,name',
			'owner:id,name,email',
			'category:id,code,name',
            ])
            ->find($project);
			
			if (!$p) return response()->json(['message' => 'Not found'], 404);
			
			return new ProjectResource($p);
		}
		
		public function store(StoreProjectRequest $request)
		{
			$data = $request->validated();
			
			$payload = [
            ...$data,
            'code' => strtoupper(trim($data['code'])),
            'name' => trim($data['name']),
            'sponsor' => array_key_exists('sponsor', $data) && $data['sponsor'] !== null ? trim($data['sponsor']) : null,
            'progress' => $data['progress'] ?? 0,
			];
			
			$project = Project::create($payload);
			
			\App\Support\Audit::log(
            $request->user()->id,
            'PROJECT',
            (int)$project->id,
            'CREATE',
            $payload
			);
			
			return response()->json(['id' => $project->id], 201);
		}
		
		public function update(UpdateProjectRequest $request, $project)
		{
			$p = Project::find($project);
			if (!$p) return response()->json(['message' => 'Not found'], 404);
			
			$data = $request->validated();
			
			// Normalize only provided fields
			if (array_key_exists('code', $data)) $data['code'] = strtoupper(trim($data['code']));
			if (array_key_exists('name', $data)) $data['name'] = trim($data['name']);
			if (array_key_exists('sponsor', $data)) $data['sponsor'] = $data['sponsor'] !== null ? trim($data['sponsor']) : null;
			
			if (empty($data)) {
				return response()->json(['ok' => true, 'message' => 'No changes']);
			}
			
			$old = $p->getOriginal();
			
			$p->fill($data);
			
			if (!$p->isDirty()) {
				return response()->json(['ok' => true, 'message' => 'No changes']);
			}
			
			$dirty = $p->getDirty(); // only changed fields
			$p->save();
			
			$changes = \App\Support\AuditDiff::diff($old, $dirty);
			
			\App\Support\Audit::log(
            $request->user()->id,
            'PROJECT',
            (int)$p->id,
            'UPDATE',
            $changes
			);
			
			return response()->json(['ok' => true]);
		}
		
		public function destroy(Request $request, $project)
		{
			$p = Project::find($project);
			if (!$p) return response()->json(['message' => 'Not found'], 404);
			
			$snapshot = [
            'code' => $p->code,
            'name' => $p->name,
            'project_status_id' => $p->project_status_id,
            'priority_id' => $p->priority_id,
            'department_id' => $p->department_id,
			];
			
			$p->delete();
			
			\App\Support\Audit::log(
            $request->user()->id,
            'PROJECT',
            (int)$project,
            'DELETE',
            [
			'mode' => 'HARD',
			'snapshot' => $snapshot,
            ]
			);
			
			return response()->json(['ok' => true, 'mode' => 'HARD']);
		}
	}
