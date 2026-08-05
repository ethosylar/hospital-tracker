<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\StoreAgreementStatusRequest;
	use App\Http\Requests\UpdateAgreementStatusRequest;
	use App\Http\Resources\AgreementStatusResource;
	use App\Models\AgreementStatus;
	use App\Support\ApiErrorCode;
	use App\Support\ApiResponse;
	use Illuminate\Http\Request;
	use Throwable;
	
	class AgreementStatusController extends Controller
	{
		public function index(Request $request)
		{
			$query = AgreementStatus::query();
			
			if ($request->filled('is_active')) {
				$query->where('is_active', $request->boolean('is_active'));
			}
			
			if ($request->filled('is_terminal')) {
				$query->where('is_terminal', $request->boolean('is_terminal'));
			}
			
			if ($request->filled('is_system_status')) {
				$query->where('is_system_status', $request->boolean('is_system_status'));
			}
			
			if ($request->filled('search')) {
				$search = trim((string) $request->search);
				$query->where(function ($where) use ($search) {
					$where->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
				});
			}
			
			$perPage = max(1, min((int) $request->get('per_page', 50), 100));
			
			return AgreementStatusResource::collection(
            $query->orderBy('sort_order')->orderBy('name')->paginate($perPage)
			);
		}
		
		public function show(AgreementStatus $status)
		{
			return new AgreementStatusResource($status);
		}
		
		public function store(StoreAgreementStatusRequest $request)
		{
			$data = $request->validated();
			$data['sort_order'] = array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : 0;
			$data['is_terminal'] = array_key_exists('is_terminal', $data) ? (bool) $data['is_terminal'] : false;
			$data['is_active'] = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true;
			$data['is_system_status'] = false;
			
			try {
				$status = AgreementStatus::create($data);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT_STATUS',
                (int) $status->id,
                'CREATE',
                [
				'code' => $status->code,
				'name' => $status->name,
				'description' => $status->description,
				'sort_order' => (int) $status->sort_order,
				'is_terminal' => (int) $status->is_terminal,
				'is_system_status' => (int) $status->is_system_status,
				'is_active' => (int) $status->is_active,
                ]
				);
				
				return (new AgreementStatusResource($status))->response()->setStatusCode(201);
				} catch (Throwable $e) {
				report($e);
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_STATUS_CREATE_FAILED,
                'Failed to create agreement status.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function update(UpdateAgreementStatusRequest $request, AgreementStatus $status)
		{
			$data = $request->validated();
			
			if (empty($data)) {
				return new AgreementStatusResource($status);
			}
			
			if ($status->is_system_status && array_key_exists('code', $data) && $data['code'] !== $status->code) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_STATUS_PROTECTED,
                'The code of a system agreement status cannot be changed.',
                [],
                422
				);
			}
			
			if ($status->is_system_status && array_key_exists('is_active', $data) && (bool) $data['is_active'] === false) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_STATUS_PROTECTED,
                'A system agreement status cannot be deactivated.',
                [],
                422
				);
			}
			
			$old = $status->getOriginal();
			$changes = \App\Support\AuditDiff::diff($old, $data);
			
			if (empty($changes)) {
				return new AgreementStatusResource($status);
			}
			
			try {
				$status->update($data);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT_STATUS',
                (int) $status->id,
                'UPDATE',
                $changes
				);
				
				return new AgreementStatusResource($status->refresh());
				} catch (Throwable $e) {
				report($e);
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_STATUS_UPDATE_FAILED,
                'Failed to update agreement status.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function destroy(Request $request, AgreementStatus $status)
		{
			if ($status->is_system_status) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_STATUS_PROTECTED,
                'A system agreement status cannot be deleted or deactivated.',
                [],
                422
				);
			}
			
			if (!$status->is_active) {
				return response()->json([
                'ok' => true,
                'mode' => 'SOFT',
                'message' => 'Agreement status is already inactive.',
				]);
			}
			
			try {
				$before = $status->getOriginal();
				$status->update(['is_active' => false]);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT_STATUS',
                (int) $status->id,
                'DELETE',
                [
				'mode' => 'SOFT',
				'snapshot' => [
				'code' => $before['code'] ?? null,
				'name' => $before['name'] ?? null,
				'description' => $before['description'] ?? null,
				'sort_order' => (int) ($before['sort_order'] ?? 0),
				'is_terminal' => (int) ($before['is_terminal'] ?? 0),
				'is_system_status' => (int) ($before['is_system_status'] ?? 0),
				'is_active' => (int) ($before['is_active'] ?? 0),
				],
				'changes' => [
				'is_active' => [
				'from' => (int) ($before['is_active'] ?? 0),
				'to' => 0,
				],
				],
                ]
				);
				
				return response()->json(['ok' => true, 'mode' => 'SOFT']);
				} catch (Throwable $e) {
				report($e);
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_STATUS_DELETE_FAILED,
                'Failed to deactivate agreement status.',
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
