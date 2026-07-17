<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\EptwSyncOneRequest;
	use App\Http\Requests\EptwSyncRequest;
	use App\Http\Resources\IntegrationSyncRunResource;
	use App\Jobs\SyncEptwPermitsJob;
	use App\Services\Eptw\EptwSyncService;
	use App\Support\ApiErrorCode;
	use App\Support\ApiResponse;
	use Throwable;
	
	class EptwSyncController extends Controller
	{
		public function sync(
        EptwSyncRequest $request,
        EptwSyncService $syncService
		) {
			$data = $request->validated();
			
			$mode = $data['mode'];
			$runAsync = $request->boolean('run_async', false);
			
			try {
				if ($runAsync) {
					SyncEptwPermitsJob::dispatch(
                    mode: $mode,
                    externalFormId: null,
                    triggeredByUserId: (int) $request->user()->id
					);
					
					return response()->json([
                    'ok' => true,
                    'queued' => true,
                    'message' => 'ePTW sync has been queued.',
                    'mode' => $mode,
					], 202);
				}
				
				$run = $syncService->syncMany(
                mode: $mode,
                triggeredByUserId: (int) $request->user()->id
				);
				
				$run->load([
                'source:id,code,name',
                'triggeredBy:id,name,email',
				]);
				
				return new IntegrationSyncRunResource($run);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::EPTW_SYNC_FAILED,
                'Failed to start ePTW synchronization.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function syncOne(
        EptwSyncOneRequest $request,
        EptwSyncService $syncService
		) {
			$data = $request->validated();
			
			$externalFormId = $data['external_form_id'];
			$runAsync = $request->boolean('run_async', false);
			
			try {
				if ($runAsync) {
					SyncEptwPermitsJob::dispatch(
                    mode: 'SINGLE',
                    externalFormId: $externalFormId,
                    triggeredByUserId: (int) $request->user()->id
					);
					
					return response()->json([
                    'ok' => true,
                    'queued' => true,
                    'message' => 'Single ePTW permit sync has been queued.',
                    'external_form_id' => $externalFormId,
					], 202);
				}
				
				$run = $syncService->syncOne(
                externalFormId: $externalFormId,
                triggeredByUserId: (int) $request->user()->id
				);
				
				$run->load([
                'source:id,code,name',
                'triggeredBy:id,name,email',
				]);
				
				return new IntegrationSyncRunResource($run);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::EPTW_SYNC_FAILED,
                'Failed to fetch ePTW permit.',
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