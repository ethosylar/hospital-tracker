<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\StoreAgreementCategoryRequest;
	use App\Http\Requests\UpdateAgreementCategoryRequest;
	use App\Http\Resources\AgreementCategoryResource;
	use App\Models\AgreementCategory;
	use App\Support\ApiErrorCode;
	use App\Support\ApiResponse;
	use Illuminate\Http\Request;
	use Throwable;
	
	class AgreementCategoryController extends Controller
	{
		public function index(Request $request)
		{
			$query = AgreementCategory::query()->withCount('types');
			
			if ($request->filled('is_active')) {
				$query->where('is_active', $request->boolean('is_active'));
			}
			
			if ($request->filled('search')) {
				$search = trim((string) $request->search);
				
				$query->where(function ($where) use ($search) {
					$where
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
				});
			}
			
			$perPage = max(1, min(
            (int) $request->get('per_page', 50),
            100
			));
			
			return AgreementCategoryResource::collection(
            $query
			->orderBy('sort_order')
			->orderBy('name')
			->paginate($perPage)
			);
		}
		
		public function show(AgreementCategory $category)
		{
			$category->loadCount('types');
			
			return new AgreementCategoryResource($category);
		}
		
		public function store(StoreAgreementCategoryRequest $request)
		{
			$data = $request->validated();
			
			$data['sort_order'] = (int) ($data['sort_order'] ?? 0);
			$data['is_active'] = (bool) ($data['is_active'] ?? true);
			$data['is_system_category'] = false;
			
			try {
				$category = AgreementCategory::create($data);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT_CATEGORY',
                (int) $category->id,
                'CREATE',
                $category->only([
				'code',
				'name',
				'description',
				'sort_order',
				'is_system_category',
				'is_active',
                ])
				);
				
				return (new AgreementCategoryResource($category))
                ->response()
                ->setStatusCode(201);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_CATEGORY_CREATE_FAILED,
                'Failed to create agreement category.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function update(
        UpdateAgreementCategoryRequest $request,
        AgreementCategory $category
		) {
			$data = $request->validated();
			
			if (
            $category->is_system_category
            && array_key_exists('code', $data)
            && $data['code'] !== $category->code
			) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_CATEGORY_PROTECTED,
                'A system agreement category code cannot be changed.',
                [],
                422
				);
			}
			
			if (
            $category->is_system_category
            && array_key_exists('is_active', $data)
            && (bool) $data['is_active'] === false
			) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_CATEGORY_PROTECTED,
                'A system agreement category cannot be deactivated.',
                [],
                422
				);
			}
			
			$changes = \App\Support\AuditDiff::diff(
            $category->getOriginal(),
            $data
			);
			
			if (empty($changes)) {
				return new AgreementCategoryResource(
                $category->loadCount('types')
				);
			}
			
			try {
				$category->update($data);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT_CATEGORY',
                (int) $category->id,
                'UPDATE',
                $changes
				);
				
				return new AgreementCategoryResource(
                $category->refresh()->loadCount('types')
				);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_CATEGORY_UPDATE_FAILED,
                'Failed to update agreement category.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function destroy(
        Request $request,
        AgreementCategory $category
		) {
			if ($category->is_system_category) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_CATEGORY_PROTECTED,
                'A system agreement category cannot be deleted.',
                [],
                422
				);
			}
			
			if ($category->types()->where('is_active', true)->exists()) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_CATEGORY_IN_USE,
                'Deactivate or move active agreement types before deactivating this category.',
                [],
                409
				);
			}
			
			if (!$category->is_active) {
				return response()->json([
                'ok' => true,
                'mode' => 'SOFT',
                'message' => 'Agreement category is already inactive.',
				]);
			}
			
			try {
				$category->update(['is_active' => false]);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT_CATEGORY',
                (int) $category->id,
                'DELETE',
                [
				'mode' => 'SOFT',
				'changes' => [
				'is_active' => ['from' => 1, 'to' => 0],
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
                ApiErrorCode::AGREEMENT_CATEGORY_DELETE_FAILED,
                'Failed to deactivate agreement category.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		private function errorDetails(Throwable $e): array
		{
			return config('app.debug')
            ? [
			'exception' => $e->getMessage(),
			'exception_class' => get_class($e),
            ]
            : [];
		}
	}	