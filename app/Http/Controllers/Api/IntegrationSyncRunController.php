<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\IntegrationSyncRunIndexRequest;
	use App\Http\Resources\IntegrationSyncRunResource;
	use App\Models\IntegrationSyncRun;
	
	class IntegrationSyncRunController extends Controller
	{
		public function index(IntegrationSyncRunIndexRequest $request)
		{
			$data = $request->validated();
			
			$q = IntegrationSyncRun::query()
            ->where('integration_code', 'EPTW')
            ->with([
			'source:id,code,name',
			'triggeredBy:id,name,email',
            ]);
			
			if (!empty($data['status'])) {
				$q->where('status', $data['status']);
			}
			
			if (!empty($data['sync_type'])) {
				$q->where('sync_type', $data['sync_type']);
			}
			
			if (!empty($data['date_from'])) {
				$q->whereDate(
                'started_at',
                '>=',
                $data['date_from']
				);
			}
			
			if (!empty($data['date_to'])) {
				$q->whereDate(
                'started_at',
                '<=',
                $data['date_to']
				);
			}
			
			$perPage = (int) ($data['per_page'] ?? 20);
			
			return IntegrationSyncRunResource::collection(
            $q->orderByDesc('started_at')
			->orderByDesc('id')
			->paginate($perPage)
			);
		}
		
		public function show(IntegrationSyncRun $run)
		{
			if ($run->integration_code !== 'EPTW') {
				abort(404);
			}
			
			$run->load([
            'source:id,code,name',
            'triggeredBy:id,name,email',
			]);
			
			return new IntegrationSyncRunResource($run);
		}
	}	