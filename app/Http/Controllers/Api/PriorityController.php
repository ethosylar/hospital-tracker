<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\StorePriorityRequest;
	use App\Http\Requests\UpdatePriorityRequest;
	use App\Http\Resources\PriorityResource;
	use App\Models\Priority;
	use App\Models\Project;
	use Illuminate\Http\Request;
	
	class PriorityController extends Controller
	{
		public function index(Request $request)
		{
			$q = Priority::query()->select(['id','code','name','sort_order','is_active','created_at','updated_at']);
			
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
			
			return PriorityResource::collection($p);
		}
		
		public function show(Priority $priority)
		{
			return new PriorityResource($priority);
		}
		
		public function store(StorePriorityRequest $request)
		{
			$data = $request->validated();
			
			$priority = Priority::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => array_key_exists('is_active', $data) ? (bool)$data['is_active'] : true,
			]);
			
			\App\Support\Audit::log(
            $request->user()->id,
            'PRIORITY',
            (int)$priority->id,
            'CREATE',
            [
			'code' => $priority->code,
			'name' => $priority->name,
			'sort_order' => $priority->sort_order,
			'is_active' => (int)$priority->is_active,
            ]
			);
			
			return (new PriorityResource($priority))
            ->response()
            ->setStatusCode(201);
		}
		
		public function update(UpdatePriorityRequest $request, Priority $priority)
		{
			$data = $request->validated();
			
			if (empty($data)) {
				return response()->json(['ok' => true, 'message' => 'No changes']);
			}
			
			$old = $priority->getOriginal();
			$changes = \App\Support\AuditDiff::diff($old, $data);
			
			if (empty($changes)) {
				return response()->json(['ok' => true, 'message' => 'No changes']);
			}
			
			if (array_key_exists('is_active', $data)) $data['is_active'] = (bool)$data['is_active'];
			if (array_key_exists('sort_order', $data) && $data['sort_order'] === null) $data['sort_order'] = 0;
			
			$priority->fill($data);
			$priority->save();
			
			\App\Support\Audit::log(
            $request->user()->id,
            'PRIORITY',
            (int)$priority->id,
            'UPDATE',
            $changes
			);
			
			return new PriorityResource($priority);
		}
		
		public function destroy(Request $request, Priority $priority)
		{
			// if used by projects => SOFT delete
			$inUse = Project::where('priority_id', $priority->id)->exists();
			
			if ($inUse) {
				$from = (int)$priority->is_active;
				$priority->update(['is_active' => false]);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'PRIORITY',
                (int)$priority->id,
                'DELETE',
                [
				'mode' => 'SOFT',
				'reason' => 'Priority is referenced by existing projects',
				'snapshot' => [
				'code' => $priority->code,
				'name' => $priority->name,
				'sort_order' => $priority->sort_order,
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
            'code' => $priority->code,
            'name' => $priority->name,
            'sort_order' => $priority->sort_order,
            'is_active' => (int)$priority->is_active,
			];
			
			$priority->delete();
			
			\App\Support\Audit::log(
            $request->user()->id,
            'PRIORITY',
            (int)$priority->id,
            'DELETE',
            [
			'mode' => 'HARD',
			'snapshot' => $snapshot,
            ]
			);
			
			return response()->json(['ok' => true, 'mode' => 'HARD']);
		}
	}
