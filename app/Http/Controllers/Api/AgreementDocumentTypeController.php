<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\AgreementDocumentTypeIndexRequest;
	use App\Http\Requests\StoreAgreementDocumentTypeRequest;
	use App\Http\Requests\UpdateAgreementDocumentTypeRequest;
	use App\Http\Resources\AgreementDocumentTypeResource;
	use App\Models\AgreementDocumentType;
	use App\Support\ApiErrorCode;
	use App\Support\ApiResponse;
	use Illuminate\Http\Request;
	use Throwable;
	
	class AgreementDocumentTypeController extends Controller
	{
		public function index(AgreementDocumentTypeIndexRequest $request)
		{
			$data = $request->validated();
			$query = AgreementDocumentType::query();
			
			if (array_key_exists('is_active', $data)) {
				$query->where('is_active', (bool) $data['is_active']);
			}
			
			if (array_key_exists('ocr_eligible', $data)) {
				$query->where(
                'ocr_eligible',
                (bool) $data['ocr_eligible']
				);
			}
			
			if (!empty($data['search'])) {
				$search = $data['search'];
				
				$query->where(function ($where) use ($search) {
					$where
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
				});
			}
			
			$perPage = max(
            1,
            min((int) ($data['per_page'] ?? 50), 100)
			);
			
			return AgreementDocumentTypeResource::collection(
            $query
			->orderBy('sort_order')
			->orderBy('name')
			->paginate($perPage)
			);
		}
		
		public function show(AgreementDocumentType $documentType)
		{
			return new AgreementDocumentTypeResource($documentType);
		}
		
		public function store(StoreAgreementDocumentTypeRequest $request)
		{
			$data = $request->validated();
			
			$data['ocr_eligible'] = array_key_exists('ocr_eligible', $data)
            ? (bool) $data['ocr_eligible']
            : true;
			
			$data['sort_order'] = (int) ($data['sort_order'] ?? 0);
			$data['is_active'] = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : true;
			$data['is_system_type'] = false;
			
			try {
				$documentType = AgreementDocumentType::create($data);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT_DOCUMENT_TYPE',
                (int) $documentType->id,
                'CREATE',
                $documentType->only([
				'code',
				'name',
				'description',
				'ocr_eligible',
				'sort_order',
				'is_system_type',
				'is_active',
                ])
				);
				
				return (new AgreementDocumentTypeResource($documentType))
                ->response()
                ->setStatusCode(201);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_DOCUMENT_TYPE_CREATE_FAILED,
                'Failed to create agreement document type.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function update(
        UpdateAgreementDocumentTypeRequest $request,
        AgreementDocumentType $documentType
		) {
			$data = $request->validated();
			
			if (
            $documentType->is_system_type
            && array_key_exists('code', $data)
            && $data['code'] !== $documentType->code
			) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_DOCUMENT_TYPE_PROTECTED,
                'The code of a system document type cannot be changed.',
                [],
                422
				);
			}
			
			if (
            $documentType->is_system_type
            && array_key_exists('is_active', $data)
            && (bool) $data['is_active'] === false
			) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_DOCUMENT_TYPE_PROTECTED,
                'A system document type cannot be deactivated.',
                [],
                422
				);
			}
			
			$changes = \App\Support\AuditDiff::diff(
            $documentType->getOriginal(),
            $data
			);
			
			if (empty($changes)) {
				return new AgreementDocumentTypeResource($documentType);
			}
			
			try {
				$documentType->update($data);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT_DOCUMENT_TYPE',
                (int) $documentType->id,
                'UPDATE',
                $changes
				);
				
				return new AgreementDocumentTypeResource(
                $documentType->refresh()
				);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_DOCUMENT_TYPE_UPDATE_FAILED,
                'Failed to update agreement document type.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function destroy(
        Request $request,
        AgreementDocumentType $documentType
		) {
			if ($documentType->is_system_type) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_DOCUMENT_TYPE_PROTECTED,
                'A system document type cannot be deleted or deactivated.',
                [],
                422
				);
			}
			
			if (!$documentType->is_active) {
				return response()->json([
                'ok' => true,
                'mode' => 'SOFT',
                'message' => 'Document type is already inactive.',
				]);
			}
			
			try {
				$documentType->update(['is_active' => false]);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT_DOCUMENT_TYPE',
                (int) $documentType->id,
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
                ApiErrorCode::AGREEMENT_DOCUMENT_TYPE_DELETE_FAILED,
                'Failed to deactivate agreement document type.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		private function errorDetails(Throwable $e): array
		{
			if (!config('app.debug')) {
				return [];
			}
			
			return [
            'exception' => $e->getMessage(),
            'exception_class' => get_class($e),
			];
		}
	}	