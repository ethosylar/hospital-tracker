<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectStatusRequest;
use App\Http\Requests\UpdateProjectStatusRequest;
use App\Http\Resources\ProjectStatusResource;
use App\Models\Project;
use App\Models\ProjectStatus;
use Illuminate\Http\Request;

class ProjectStatusController extends Controller
{
    public function index(Request $request)
    {
        $q = ProjectStatus::query()
            ->select(['id','code','name','sort_order','is_active','created_at','updated_at']);

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

        return ProjectStatusResource::collection($p);
    }

    public function show($status)
    {
        $row = ProjectStatus::findOrFail($status);
        return new ProjectStatusResource($row);
    }

    public function store(StoreProjectStatusRequest $request)
    {
        $data = $request->validated();

        $payload = [
            'code' => strtoupper(trim($data['code'])),
            'name' => trim($data['name']),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => array_key_exists('is_active', $data) ? (bool)$data['is_active'] : true,
        ];

        $row = ProjectStatus::create($payload);

        \App\Support\Audit::log(
            $request->user()->id,
            'PROJECT_STATUS',
            (int)$row->id,
            'CREATE',
            $payload
        );

        return response()->json(['id' => $row->id], 201);
    }

    public function update(UpdateProjectStatusRequest $request, $status)
    {
        $row = ProjectStatus::findOrFail($status);

        $data = $request->validated();
        if (empty($data)) return response()->json(['ok' => true, 'message' => 'No changes']);

        // normalize only the keys that exist
        if (array_key_exists('code', $data)) $data['code'] = strtoupper(trim($data['code']));
        if (array_key_exists('name', $data)) $data['name'] = trim($data['name']);
        if (array_key_exists('is_active', $data)) $data['is_active'] = (bool)$data['is_active'];

        $old = $row->getOriginal();

        $row->fill($data);

        if (!$row->isDirty()) {
            return response()->json(['ok' => true, 'message' => 'No changes']);
        }

        $dirty = $row->getDirty();
        $row->save();

        $changes = \App\Support\AuditDiff::diff($old, $dirty);

        \App\Support\Audit::log(
            $request->user()->id,
            'PROJECT_STATUS',
            (int)$row->id,
            'UPDATE',
            $changes
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, $status)
    {
        $row = ProjectStatus::findOrFail($status);

        // If used by projects -> SOFT delete
        $inUse = Project::query()->where('project_status_id', $row->id)->exists();

        if ($inUse) {
            $from = (bool)$row->is_active;

            $row->update(['is_active' => false]);

            \App\Support\Audit::log(
                $request->user()->id,
                'PROJECT_STATUS',
                (int)$row->id,
                'DELETE',
                [
                    'mode' => 'SOFT',
                    'reason' => 'Status is referenced by existing projects',
                    'snapshot' => [
                        'code' => $row->code,
                        'name' => $row->name,
                        'sort_order' => $row->sort_order,
                        'is_active' => $from,
                    ],
                    'changes' => [
                        'is_active' => ['from' => (int)$from, 'to' => 0],
                    ],
                ]
            );

            return response()->json(['ok' => true, 'mode' => 'SOFT']);
        }

        // Not used -> HARD delete
        $snapshot = [
            'code' => $row->code,
            'name' => $row->name,
            'sort_order' => $row->sort_order,
            'is_active' => (bool)$row->is_active,
        ];

        $row->delete();

        \App\Support\Audit::log(
            $request->user()->id,
            'PROJECT_STATUS',
            (int)$status,
            'DELETE',
            [
                'mode' => 'HARD',
                'snapshot' => $snapshot,
            ]
        );

        return response()->json(['ok' => true, 'mode' => 'HARD']);
    }
}
