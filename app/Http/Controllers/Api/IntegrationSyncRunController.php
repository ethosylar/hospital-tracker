<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IntegrationSyncRunIndexRequest;
use App\Http\Resources\IntegrationSyncRunResource;
use App\Models\IntegrationSyncRun;
use App\Support\SiteAccess;

class IntegrationSyncRunController extends Controller
{
	public function index(IntegrationSyncRunIndexRequest $request)
	{
		$data = $request->validated();

		$query = IntegrationSyncRun::query()
			->where('integration_code', 'EPTW')
			->with([
				'site:id,code,name,short_name',
				'source:id,site_id,code,name',
				'triggeredBy:id,name,email',
			]);

		SiteAccess::applyViewScope($query, $request->user(), 'dt_integration_sync_runs.site_id');

		if (!empty($data['site_id'])) {
			$query->where('dt_integration_sync_runs.site_id', (int) $data['site_id']);
		}

		if (!empty($data['status'])) {
			$query->where('status', $data['status']);
		}

		if (!empty($data['sync_type'])) {
			$query->where('sync_type', $data['sync_type']);
		}

		if (!empty($data['date_from'])) {
			$query->whereDate('started_at', '>=', $data['date_from']);
		}

		if (!empty($data['date_to'])) {
			$query->whereDate('started_at', '<=', $data['date_to']);
		}

		$perPage = max(1, min((int) ($data['per_page'] ?? 20), 100));

		return IntegrationSyncRunResource::collection(
			$query
				->orderByDesc('started_at')
				->orderByDesc('id')
				->paginate($perPage)
		);
	}

	public function show($run)
	{
		/*
         * Middleware already verifies Site,
         * but resolving again through the scoped query
         * protects this method if reused elsewhere.
         */
		$run = request()->route('run');

		if (!$run instanceof IntegrationSyncRun || $run->integration_code !== 'EPTW') {
			abort(404);
		}

		$run->load([
			'site:id,code,name,short_name',
			'source:id,site_id,code,name',
			'triggeredBy:id,name,email',
		]);

		return new IntegrationSyncRunResource($run);
	}
}
