<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\ImportEptwPermitsRequest;
	use App\Http\Resources\IntegrationSyncRunResource;
	use App\Models\ExternalSource;
	use App\Services\Eptw\EptwPermitImportService;
	use App\Support\ApiErrorCode;
	use App\Support\ApiResponse;
	use Throwable;
	
	class EptwImportController extends Controller
	{
		public function store(
        ImportEptwPermitsRequest $request,
        EptwPermitImportService $importService
		) {
			$source = ExternalSource::query()
            ->where('code', 'EPTW')
            ->where('is_active', true)
            ->first();
			
			if (!$source) {
				return ApiResponse::error(
                ApiErrorCode::EPTW_SOURCE_NOT_CONFIGURED,
                'The EPTW external source is not configured.',
                422
				);
			}
			
			$data = $request->validated();
			
			try {
				$run = $importService->import(
                source: $source,
                records: $data['permits'],
                triggeredByUserId: (int) $request->user()->id,
                syncType: $data['sync_type'] ?? 'MANUAL',
                cursorFrom: $data['cursor_from'] ?? null,
                cursorTo: $data['cursor_to'] ?? null
				);
				
				$run->load([
                'source:id,code,name',
                'triggeredBy:id,name,email',
				]);
				
				return (new IntegrationSyncRunResource($run))
                ->response()
                ->setStatusCode(201);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::EPTW_IMPORT_FAILED,
                'Failed to import ePTW permits.',
                500
				);
			}
		}
	}	