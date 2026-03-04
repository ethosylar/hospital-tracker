<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $q = Department::query()->select(['id','code','name','is_active','created_at','updated_at']);

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

        $perPage = max(1, min((int)$request->get('per_page', 20), 100));

        return DepartmentResource::collection(
            $q->orderBy('name')->paginate($perPage)
        );
    }

    public function show($department)
    {
        $row = Department::query()
            ->select(['id','code','name','is_active','created_at','updated_at'])
            ->find($department);

        if (!$row) return response()->json(['message' => 'Not found'], 404);

        return new DepartmentResource($row);
    }

    public function store(StoreDepartmentRequest $request)
    {
        $data = $request->validated();

        $payload = [
            'code' => strtoupper(trim($data['code'])),
            'name' => trim($data['name']),
            'is_active' => array_key_exists('is_active', $data) ? (bool)$data['is_active'] : true,
        ];

        $dept = Department::create($payload);

        \App\Support\Audit::log(
            $request->user()->id,
            'DEPARTMENT',
            (int)$dept->id,
            'CREATE',
            $payload
        );

        return response()->json(['id' => $dept->id], 201);
    }

    public function update(UpdateDepartmentRequest $request, $department)
    {
        $dept = Department::find($department);
        if (!$dept) return response()->json(['message' => 'Not found'], 404);

        $data = $request->validated();

        // normalize (only for provided fields)
        if (array_key_exists('code', $data)) $data['code'] = strtoupper(trim($data['code']));
        if (array_key_exists('name', $data)) $data['name'] = trim($data['name']);
        if (array_key_exists('is_active', $data)) $data['is_active'] = (bool)$data['is_active'];

        if (empty($data)) {
            return response()->json(['ok' => true, 'message' => 'No changes']);
        }

        $old = $dept->getOriginal();

        $dept->fill($data);

        if (!$dept->isDirty()) {
            return response()->json(['ok' => true, 'message' => 'No changes']);
        }

        $dirty = $dept->getDirty(); // only changed fields
        $dept->save();

        $changes = \App\Support\AuditDiff::diff($old, $dirty);

        \App\Support\Audit::log(
            $request->user()->id,
            'DEPARTMENT',
            (int)$dept->id,
            'UPDATE',
            $changes
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, $department)
    {
        $dept = Department::find($department);
        if (!$dept) return response()->json(['message' => 'Not found'], 404);

        // IMPORTANT: use Eloquent existence check, keep rule same as your original
        $inUse = \App\Models\Project::where('department_id', $dept->id)->exists();

        if ($inUse) {
            $from = (bool)$dept->is_active;

            $dept->update(['is_active' => false]);

            \App\Support\Audit::log(
                $request->user()->id,
                'DEPARTMENT',
                (int)$dept->id,
                'DELETE',
                [
                    'mode' => 'SOFT',
                    'reason' => 'Department is referenced by existing projects',
                    'snapshot' => [
                        'code' => $dept->code,
                        'name' => $dept->name,
                        'is_active' => $from,
                    ],
                    'changes' => [
                        'is_active' => ['from' => $from ? 1 : 0, 'to' => 0],
                    ],
                ]
            );

            return response()->json(['ok' => true, 'mode' => 'SOFT']);
        }

        // Hard delete
        $snapshot = [
            'code' => $dept->code,
            'name' => $dept->name,
            'is_active' => (bool)$dept->is_active,
        ];

        $dept->delete();

        \App\Support\Audit::log(
            $request->user()->id,
            'DEPARTMENT',
            (int)$department,
            'DELETE',
            [
                'mode' => 'HARD',
                'snapshot' => $snapshot,
            ]
        );

        return response()->json(['ok' => true, 'mode' => 'HARD']);
    }
}
