<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportEptwPermitsRequest;
use App\Http\Resources\IntegrationSyncRunResource;
use App\Models\ExternalSource;
use App\Services\Eptw\EptwPermitImportService;
use App\Support\ApiErrorCode;
use App\Support\ApiResponse;
use App\Support\SiteAccess;
use Throwable;

class EptwImportController extends Controller
{
	public function store(ImportEptwPermitsRequest $request, EptwPermitImportService $importService)
	{
		$data = $request->validated();
		$siteId = (int) $data['site_id'];

		if (!SiteAccess::canManageSite($request->user(), $siteId)) {
			return ApiResponse::error(
				ApiErrorCode::EPTW_SITE_ACCESS_DENIED,
				'You do not have management access to this Site.',
				[],
				403
			);
		}

		$source = ExternalSource::query()
			->where('site_id', $siteId)
			->where('code', 'EPTW')
			->where('is_active', true)
			->first();

		if (!$source) {
			return ApiResponse::error(
				ApiErrorCode::EPTW_SOURCE_NOT_CONFIGURED,
				'The EPTW external source is not configured for this Site.',
				[],
				422
			);
		}

		try {
			$run =
				$importService->import(
					source: $source,
					records: $data['permits'],
					triggeredByUserId: (int) $request->user()->id,
					syncType: $data['sync_type'] ?? 'MANUAL',
					cursorFrom: $data['cursor_from'] ?? null,
					cursorTo: $data['cursor_to'] ?? null
				);

			$run->load([
				'site:id,code,name,short_name',
				'source:id,site_id,code,name',
				'triggeredBy:id,name,email',
			]);

			return (new IntegrationSyncRunResource($run))->response()->setStatusCode(201);
		} catch (Throwable $e) {
			report($e);
			return ApiResponse::error(
				ApiErrorCode::EPTW_IMPORT_FAILED,
				'Failed to import ePTW permits.',
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
