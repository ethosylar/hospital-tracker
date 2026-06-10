<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\StoreProjectBudgetLineRequest;
	use App\Http\Requests\UpdateProjectBudgetLineRequest;
	use App\Http\Resources\ProjectBudgetLineResource;
	use App\Models\Project;
	use App\Models\ProjectBudgetLine;
	use App\Support\ApiErrorCode;
	use App\Support\ApiResponse;
	use Illuminate\Http\Request;
	
	class ProjectBudgetLineController extends Controller
	{
		public function index(Request $request, Project $project)
		{
			$q = $project->budgetLines()->select([
            'id','project_id','line_type','code','name',
            'planned_amount','actual_amount','committed_amount',
            'sort_order','is_active','notes','created_at','updated_at'
			]);
			
			if ($request->filled('line_type')) {
				$q->where('line_type', strtoupper(trim($request->line_type)));
			}
			if ($request->filled('is_active')) {
				$q->where('is_active', (int)$request->is_active);
			}
			
			$perPage = max(1, min((int)$request->get('per_page', 50), 100));
			
			return ProjectBudgetLineResource::collection(
            $q->orderBy('line_type')->orderBy('sort_order')->orderBy('name')->paginate($perPage)
			);
		}
		
		public function show(Project $project, ProjectBudgetLine $line)
		{
			if ((int)$line->project_id !== (int)$project->id) abort(404);
			return new ProjectBudgetLineResource($line);
		}
		
		public function store(StoreProjectBudgetLineRequest $request, Project $project)
		{
			$data = $request->validated();
			
			$payload = [
            'project_id' => (int)$project->id,
            'line_type' => $data['line_type'] ?? 'COST',
            'code' => $data['code'],
            'name' => $data['name'],
            'planned_amount' => $data['planned_amount'] ?? 0,
            'actual_amount' => $data['actual_amount'] ?? 0,
            'committed_amount' => $data['committed_amount'] ?? 0,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => array_key_exists('is_active', $data) ? (bool)$data['is_active'] : true,
            'notes' => $data['notes'] ?? null,
			];
			
			// unique per project/type/code
			$exists = ProjectBudgetLine::query()
            ->where('project_id', $project->id)
            ->where('line_type', $payload['line_type'])
            ->where('code', $payload['code'])
            ->exists();
			
			if ($exists) {
				return ApiResponse::error(
                ApiErrorCode::PROJECT_BUDGET_LINE_DUPLICATE_CODE,
                'Duplicate code for this project and line_type',
                409
				);
			}
			
			try {
				$line = ProjectBudgetLine::create($payload);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'PROJECT_BUDGET_LINE',
                (int)$line->id,
                'CREATE',
                $payload
				);
				
				return (new ProjectBudgetLineResource($line))->response()->setStatusCode(201);
				
				} catch (\Throwable $e) {
				report($e);
				return ApiResponse::error(
                ApiErrorCode::PROJECT_BUDGET_LINE_CREATE_FAILED,
                'Failed to create budget line',
                500
				);
			}
		}
		
		public function update(UpdateProjectBudgetLineRequest $request, Project $project, ProjectBudgetLine $line)
		{
			if ((int)$line->project_id !== (int)$project->id) abort(404);
			
			$data = $request->validated();
			if (empty($data)) return response()->json(['ok' => true, 'message' => 'No changes']);
			
			// check unique if code/type changed
			$nextType = array_key_exists('line_type', $data) ? $data['line_type'] : $line->line_type;
			$nextCode = array_key_exists('code', $data) ? $data['code'] : $line->code;
			
			$dup = ProjectBudgetLine::query()
            ->where('project_id', $project->id)
            ->where('line_type', $nextType)
            ->where('code', $nextCode)
            ->where('id', '!=', $line->id)
            ->exists();
			
			if ($dup) {
				return ApiResponse::error(
                ApiErrorCode::PROJECT_BUDGET_LINE_DUPLICATE_CODE,
                'Duplicate code for this project and line_type',
                409
				);
			}
			
			$old = $line->getOriginal();
			$line->fill($data);
			
			if (!$line->isDirty()) return response()->json(['ok' => true, 'message' => 'No changes']);
			
			$dirty = $line->getDirty();
			
			try {
				$line->save();
				
				$changes = \App\Support\AuditDiff::diff($old, $dirty);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'PROJECT_BUDGET_LINE',
                (int)$line->id,
                'UPDATE',
                $changes
				);
				
				return new ProjectBudgetLineResource($line->refresh());
				
				} catch (\Throwable $e) {
				report($e);
				return ApiResponse::error(
                ApiErrorCode::PROJECT_BUDGET_LINE_UPDATE_FAILED,
                'Failed to update budget line',
                500
				);
			}
		}
		
		public function destroy(Request $request, Project $project, ProjectBudgetLine $line)
		{
			if ((int)$line->project_id !== (int)$project->id) abort(404);
			
			try {
				$snapshot = [
                'project_id' => (int)$project->id,
                'line_type' => $line->line_type,
                'code' => $line->code,
                'name' => $line->name,
                'planned_amount' => (float)$line->planned_amount,
                'actual_amount' => (float)$line->actual_amount,
                'committed_amount' => (float)$line->committed_amount,
                'is_active' => (int)$line->is_active,
				];
				
				$id = (int)$line->id;
				$line->delete();
				
				\App\Support\Audit::log(
                $request->user()->id,
                'PROJECT_BUDGET_LINE',
                $id,
                'DELETE',
                ['mode' => 'HARD', 'snapshot' => $snapshot]
				);
				
				return response()->json(['ok' => true, 'mode' => 'HARD']);
				
				} catch (\Throwable $e) {
				report($e);
				return ApiResponse::error(
                ApiErrorCode::PROJECT_BUDGET_LINE_DELETE_FAILED,
                'Failed to delete budget line',
                500
				);
			}
		}
	}	