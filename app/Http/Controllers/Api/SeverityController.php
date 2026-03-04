<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\StoreSeverityRequest;
	use App\Http\Requests\UpdateSeverityRequest;
	use App\Http\Resources\SeverityResource;
	use App\Models\ExternalRiskIssue;
	use App\Models\Severity;
	use Illuminate\Http\Request;
	
	class SeverityController extends Controller
	{
		public function index(Request $request)
		{
			$q = Severity::query();
			
			if ($request->filled('is_active')) {
				$q->where('is_active', (int)$request->is_active);
			}
			
			if ($request->filled('search')) {
				$s = trim($request->search);
				$q->where(fn($w) => $w->where('code', 'like', "%{$s}%")
				->orWhere('name', 'like', "%{$s}%"));
			}
			
			$perPage = max(1, min((int)$request->get('per_page', 50), 100));
			$p = $q->orderBy('sort_order')->orderBy('name')->paginate($perPage);
			
			return SeverityResource::collection($p);
		}
		
		public function show(Severity $severity)
		{
			return new SeverityResource($severity);
		}
		
		public function store(StoreSeverityRequest $request)
		{
			$data = $request->validated();
			
			$data['sort_order'] = $data['sort_order'] ?? 0;
			$data['is_active']  = array_key_exists('is_active', $data) ? (int)$data['is_active'] : 1;
			
			$severity = Severity::create($data);
			
			\App\Support\Audit::log(
            $request->user()->id,
            'SEVERITY',
            (int)$severity->id,
            'CREATE',
            $data
			);
			
			return (new SeverityResource($severity))
            ->response()
            ->setStatusCode(201);
		}
		
		public function update(UpdateSeverityRequest $request, Severity $severity)
		{
			$data = $request->validated();
			
			if (empty($data)) {
				return response()->json(['ok' => true, 'message' => 'No changes']);
			}
			
			$old = $severity->getOriginal();
			
			$severity->fill($data);
			$dirty = $severity->getDirty();
			
			if (empty($dirty)) {
				return response()->json(['ok' => true, 'message' => 'No changes']);
			}
			
			$changes = \App\Support\AuditDiff::diff($old, $dirty);
			
			$severity->save();
			
			\App\Support\Audit::log(
            $request->user()->id,
            'SEVERITY',
            (int)$severity->id,
            'UPDATE',
            $changes
			);
			
			return new SeverityResource($severity->fresh());
		}
		
		public function destroy(Request $request, Severity $severity)
		{
			// If used by external risk/issues, soft delete (deactivate)
			$inUse = ExternalRiskIssue::where('severity_id', $severity->id)->exists();
			
			if ($inUse) {
				$from = (int)$severity->is_active;
				
				$severity->update(['is_active' => false]);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'SEVERITY',
                (int)$severity->id,
                'DELETE',
                [
				'mode' => 'SOFT',
				'reason' => 'Severity is referenced by existing risk/issues',
				'snapshot' => [
				'code' => $severity->code,
				'name' => $severity->name,
				'sort_order' => $severity->sort_order,
				'is_active' => $from,
				],
				'changes' => [
				'is_active' => ['from' => $from, 'to' => 0],
				],
                ]
				);
				
				return response()->json(['ok' => true, 'mode' => 'SOFT']);
			}
			
			// Not used: hard delete
			$snapshot = [
            'code' => $severity->code,
            'name' => $severity->name,
            'sort_order' => $severity->sort_order,
            'is_active' => (int)$severity->is_active,
			];
			
			$severity->delete();
			
			\App\Support\Audit::log(
            $request->user()->id,
            'SEVERITY',
            (int)$severity->id,
            'DELETE',
            [
			'mode' => 'HARD',
			'snapshot' => $snapshot,
            ]
			);
			
			return response()->json(['ok' => true, 'mode' => 'HARD']);
		}
	}
