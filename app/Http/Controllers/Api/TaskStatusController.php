<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\StoreTaskStatusRequest;
	use App\Http\Requests\UpdateTaskStatusRequest;
	use App\Http\Resources\TaskStatusResource;
	use App\Models\TaskStatus;
	use App\Models\ProjectTask;
	use Illuminate\Http\Request;
	
	class TaskStatusController extends Controller
	{
		public function index(Request $request)
		{
			$q = TaskStatus::query()->select(['id','code','name','sort_order','is_active','created_at','updated_at']);
			
			if ($request->filled('is_active')) {
				$q->where('is_active', (int)$request->is_active);
			}
			
			if ($request->filled('search')) {
				$s = trim($request->search);
				$q->where(function ($w) use ($s) {
					$w->where('code', 'like', "%{$s}%")
					->orWhere('name', 'like', "%{$s}%");
				});
			}
			
			$perPage = max(1, min((int)$request->get('per_page', 50), 100));
			$p = $q->orderBy('sort_order')->orderBy('name')->paginate($perPage);
			
			return TaskStatusResource::collection($p);
		}
		
		// route-model-binding gives you centralised 404 response
		public function show(TaskStatus $status)
		{
			return new TaskStatusResource($status);
		}
		
		public function store(StoreTaskStatusRequest $request)
		{
			$data = $request->validated();
			
			$status = TaskStatus::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => array_key_exists('is_active', $data) ? (bool)$data['is_active'] : true,
			]);
			
			\App\Support\Audit::log(
            $request->user()->id,
            'TASK_STATUS',
            (int)$status->id,
            'CREATE',
            [
			'code' => $status->code,
			'name' => $status->name,
			'sort_order' => $status->sort_order,
			'is_active' => (int)$status->is_active,
            ]
			);
			
			return (new TaskStatusResource($status))
            ->response()
            ->setStatusCode(201);
		}
		
		public function update(UpdateTaskStatusRequest $request, TaskStatus $status)
		{
			$data = $request->validated();
			
			if (empty($data)) {
				return response()->json(['ok' => true, 'message' => 'No changes']);
			}
			
			$old = $status->getOriginal();
			$changes = \App\Support\AuditDiff::diff($old, $data);
			
			if (empty($changes)) {
				return response()->json(['ok' => true, 'message' => 'No changes']);
			}
			
			if (array_key_exists('is_active', $data)) {
				$data['is_active'] = (bool)$data['is_active'];
			}
			if (array_key_exists('sort_order', $data) && $data['sort_order'] === null) {
				$data['sort_order'] = 0;
			}
			
			$status->fill($data);
			$status->save();
			
			\App\Support\Audit::log(
            $request->user()->id,
            'TASK_STATUS',
            (int)$status->id,
            'UPDATE',
            $changes
			);
			
			return new TaskStatusResource($status);
		}
		
		public function destroy(Request $request, TaskStatus $status)
		{
			// If used by tasks => SOFT delete
			$inUse = ProjectTask::where('task_status_id', $status->id)->exists();
			
			if ($inUse) {
				$from = (int)$status->is_active;
				$status->update(['is_active' => false]);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'TASK_STATUS',
                (int)$status->id,
                'DELETE',
                [
				'mode' => 'SOFT',
				'reason' => 'Status is referenced by existing tasks',
				'snapshot' => [
				'code' => $status->code,
				'name' => $status->name,
				'sort_order' => $status->sort_order,
				'is_active' => $from,
				],
				'changes' => [
				'is_active' => ['from' => $from, 'to' => 0],
				],
                ]
				);
				
				return response()->json(['ok' => true, 'mode' => 'SOFT']);
			}
			
			// HARD delete
			$snapshot = [
            'code' => $status->code,
            'name' => $status->name,
            'sort_order' => $status->sort_order,
            'is_active' => (int)$status->is_active,
			];
			
			$status->delete();
			
			\App\Support\Audit::log(
            $request->user()->id,
            'TASK_STATUS',
            (int)$status->id,
            'DELETE',
            [
			'mode' => 'HARD',
			'snapshot' => $snapshot,
            ]
			);
			
			return response()->json(['ok' => true, 'mode' => 'HARD']);
		}
	}
