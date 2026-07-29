<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\StoreAgreementTypeRequest;
	use App\Http\Requests\UpdateAgreementTypeRequest;
	use App\Http\Resources\AgreementTypeResource;
	use App\Models\AgreementType;
	use App\Support\ApiErrorCode;
	use App\Support\ApiResponse;
	use Illuminate\Http\Request;
	use Throwable;
	
	class AgreementTypeController extends Controller
	{
		public function index(Request $request)
		{
			$query = AgreementType::query()->with(
            'category:id,code,name'
			);
			
			if ($request->filled('agreement_category_id')) {
				$query->where(
                'agreement_category_id',
                (int) $request->agreement_category_id
				);
			}
			
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
			
			return AgreementTypeResource::collection(
            $query
			->orderBy('sort_order')
			->orderBy('name')
			->paginate($perPage)
			);
		}
		
		public function show(AgreementType $type)
		{
			$type->load('category:id,code,name');
			
			return new AgreementTypeResource($type);
		}
		
		public function store(StoreAgreementTypeRequest $request)
		{
			$data = $request->validated();
			
			$data['sort_order'] = (int) ($data['sort_order'] ?? 0);
			$data['is_active'] = (bool) ($data['is_active'] ?? true);
			$data['is_system_type'] = false;
			
			try {
				$type = AgreementType::create($data);
				$type->load('category:id,code,name');
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT_TYPE',
                (int) $type->id,
                'CREATE',
                $type->only([
				'agreement_category_id',
				'code',
				'name',
				'description',
				'sort_order',
				'is_system_type',
				'is_active',
                ])
				);
				
				return (new AgreementTypeResource($type))
                ->response()
                ->setStatusCode(201);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_TYPE_CREATE_FAILED,
                'Failed to create agreement type.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function update(
        UpdateAgreementTypeRequest $request,
        AgreementType $type
		) {
			$data = $request->validated();
			
			if (
            $type->is_system_type
            && array_key_exists('code', $data)
            && $data['code'] !== $type->code
			) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_TYPE_PROTECTED,
                'A system agreement type code cannot be changed.',
                [],
                422
				);
			}
			
			if (
            $type->is_system_type
            && array_key_exists('is_active', $data)
            && (bool) $data['is_active'] === false
			) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_TYPE_PROTECTED,
                'A system agreement type cannot be deactivated.',
                [],
                422
				);
			}
			
			$changes = \App\Support\AuditDiff::diff(
            $type->getOriginal(),
            $data
			);
			
			if (empty($changes)) {
				return new AgreementTypeResource(
                $type->load('category:id,code,name')
				);
			}
			
			try {
				$type->update($data);
				$type->refresh()->load('category:id,code,name');
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT_TYPE',
                (int) $type->id,
                'UPDATE',
                $changes
				);
				
				return new AgreementTypeResource($type);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_TYPE_UPDATE_FAILED,
                'Failed to update agreement type.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function destroy(
        Request $request,
        AgreementType $type
		) {
			if ($type->is_system_type) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_TYPE_PROTECTED,
                'A system agreement type cannot be deleted.',
                [],
                422
				);
			}
			
			if (!$type->is_active) {
				return response()->json([
                'ok' => true,
                'mode' => 'SOFT',
                'message' => 'Agreement type is already inactive.',
				]);
			}
			
			try {
				$type->update(['is_active' => false]);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT_TYPE',
                (int) $type->id,
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
                ApiErrorCode::AGREEMENT_TYPE_DELETE_FAILED,
                'Failed to deactivate agreement type.',
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