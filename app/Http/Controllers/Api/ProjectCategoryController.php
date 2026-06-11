<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\StoreProjectCategoryRequest;
	use App\Http\Requests\UpdateProjectCategoryRequest;
	use App\Http\Resources\ProjectCategoryResource;
	use App\Models\Project;
	use App\Models\ProjectCategory;
	use App\Support\ApiErrorCode;
	use App\Support\ApiResponse;
	use Illuminate\Http\Request;
	
	class ProjectCategoryController extends Controller
	{
		public function index(Request $request)
		{
			$q = ProjectCategory::query()->select(['id','code','name','group','year','sort_order','is_active','created_at','updated_at']);
			
			if ($request->filled('is_active')) $q->where('is_active', (int)$request->is_active);
			
			if ($request->filled('search')) {
				$s = trim($request->search);
				$q->where(fn($w) =>
                $w->where('code','like',"%{$s}%")
				->orWhere('name','like',"%{$s}%")
				->orWhere('group','like',"%{$s}%")
				);
			}
			
			$perPage = max(1, min((int)$request->get('per_page', 50), 100));
			
			return ProjectCategoryResource::collection(
            $q->orderBy('sort_order')->orderBy('name')->paginate($perPage)
			);
		}
		
		public function show(ProjectCategory $category)
		{
			return new ProjectCategoryResource($category);
		}
		
		public function store(StoreProjectCategoryRequest $request)
		{
			$data = $request->validated();
			
			$payload = [
            'code' => $data['code'],
            'name' => $data['name'],
            'group' => $data['group'] ?? null,
            'year' => $data['year'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => array_key_exists('is_active', $data) ? (bool)$data['is_active'] : true,
			];
			
			try {
				$cat = ProjectCategory::create($payload);
				
				\App\Support\Audit::log($request->user()->id, 'PROJECT_CATEGORY', (int)$cat->id, 'CREATE', $payload);
				
				return (new ProjectCategoryResource($cat))->response()->setStatusCode(201);
				
				} catch (\Throwable $e) {
				report($e);
				return ApiResponse::error(ApiErrorCode::PROJECT_CATEGORY_CREATE_FAILED, 'Failed to create project category', 500);
			}
		}
		
		public function update(UpdateProjectCategoryRequest $request, ProjectCategory $category)
		{
			$data = $request->validated();
			if (empty($data)) return response()->json(['ok' => true, 'message' => 'No changes']);
			
			$old = $category->getOriginal();
			$category->fill($data);
			
			if (!$category->isDirty()) return response()->json(['ok' => true, 'message' => 'No changes']);
			
			$dirty = $category->getDirty();
			
			try {
				$category->save();
				
				$changes = \App\Support\AuditDiff::diff($old, $dirty);
				\App\Support\Audit::log($request->user()->id, 'PROJECT_CATEGORY', (int)$category->id, 'UPDATE', $changes);
				
				return new ProjectCategoryResource($category->refresh());
				
				} catch (\Throwable $e) {
				report($e);
				return ApiResponse::error(ApiErrorCode::PROJECT_CATEGORY_UPDATE_FAILED, 'Failed to update project category', 500);
			}
		}
		
		public function destroy(Request $request, ProjectCategory $category)
		{
			try {
				$inUse = Project::where('project_category_id', $category->id)->exists();
				
				if ($inUse) {
					$from = (int)$category->is_active;
					$category->update(['is_active' => false]);
					
					\App\Support\Audit::log(
                    $request->user()->id,
                    'PROJECT_CATEGORY',
                    (int)$category->id,
                    'DELETE',
                    [
					'mode' => 'SOFT',
					'reason' => 'Category is referenced by existing projects',
					'snapshot' => ['code' => $category->code, 'name' => $category->name, 'is_active' => $from],
					'changes' => ['is_active' => ['from' => $from, 'to' => 0]],
                    ]
					);
					
					return response()->json(['ok' => true, 'mode' => 'SOFT']);
				}
				
				$snapshot = ['code' => $category->code, 'name' => $category->name, 'is_active' => (int)$category->is_active];
				$id = (int)$category->id;
				
				$category->delete();
				
				\App\Support\Audit::log($request->user()->id, 'PROJECT_CATEGORY', $id, 'DELETE', ['mode' => 'HARD', 'snapshot' => $snapshot]);
				
				return response()->json(['ok' => true, 'mode' => 'HARD']);
				
				} catch (\Throwable $e) {
				report($e);
				return ApiResponse::error(ApiErrorCode::PROJECT_CATEGORY_DELETE_FAILED, 'Failed to delete project category', 500);
			}
		}
	}	