<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepartmentIndexRequest;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Agreement;
use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use App\Support\ApiErrorCode;
use App\Support\ApiResponse;
use App\Support\SiteAccess;
use Illuminate\Http\Request;
use Throwable;

class DepartmentController extends Controller
{
    public function index(DepartmentIndexRequest $request)
    {
        $data = $request->validated();

        $query = Department::query()->with(['site:id,code,name,short_name,site_type,is_active',]);
        SiteAccess::applyViewScope($query, $request->user(), 'lt_departments.site_id');

        if (!empty($data['site_id'])) {
            $query->where('lt_departments.site_id', (int) $data['site_id']);
        }

        if (array_key_exists('is_active', $data)) {
            $query->where('lt_departments.is_active', (bool) $data['is_active']);
        }

        if (!empty($data['search'])) {
            $search = $data['search'];

            $query->where(function ($where) use ($search) {
                $where
                    ->where('lt_departments.code', 'like', "%{$search}%")
                    ->orWhere('lt_departments.name', 'like', "%{$search}%");
            });
        }

        $perPage = max(1, min((int) ($data['per_page'] ?? 20), 100));

        return DepartmentResource::collection(
            $query
                ->orderBy('lt_departments.site_id')
                ->orderBy('lt_departments.name')
                ->paginate($perPage)
        );
    }

    public function show(Request $request, $department)
    {
        $query = Department::query()
            ->with(['site:id,code,name,short_name,site_type,is_active',]);

        SiteAccess::applyViewScope($query, $request->user(), 'lt_departments.site_id');

        $row = $query->find($department);

        if (!$row) {
            return ApiResponse::error(ApiErrorCode::DEPARTMENT_NOT_FOUND, 'Department was not found.', [], 404);
        }

        return new DepartmentResource($row);
    }

    public function store(StoreDepartmentRequest $request)
    {
        $data = $request->validated();
        $siteId = (int) $data['site_id'];

        if (!SiteAccess::canManageSite($request->user(), $siteId)) {
            return ApiResponse::error(
                ApiErrorCode::DEPARTMENT_SITE_ACCESS_DENIED,
                'You do not have permission to manage departments for the selected site.',
                ['site_id' => $siteId,],
                403
            );
        }

        $duplicate = Department::query()
            ->where('site_id', $siteId)
            ->where('code', $data['code'])
            ->exists();

        if ($duplicate) {
            return ApiResponse::error(
                ApiErrorCode::DEPARTMENT_DUPLICATE_CODE,
                'A department with the same code already exists for this site.',
                ['site_id' => $siteId, 'code' => $data['code'],],
                409
            );
        }

        $payload = [
            'site_id' => $siteId,
            'code' => $data['code'],
            'name' => $data['name'],
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
        ];

        try {
            $department = Department::create($payload);

            \App\Support\Audit::log(
                $request->user()->id,
                'DEPARTMENT',
                (int) $department->id,
                'CREATE',
                $payload
            );

            $department->load(['site:id,code,name,short_name,site_type,is_active',]);

            return (new DepartmentResource($department))
                ->response()
                ->setStatusCode(201);
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                ApiErrorCode::DEPARTMENT_CREATE_FAILED,
                'Failed to create department.',
                $this->errorDetails($e),
                500
            );
        }
    }

    public function update(UpdateDepartmentRequest $request, $department)
    {
        $query = Department::query();

        SiteAccess::applyViewScope($query, $request->user(), 'lt_departments.site_id');

        $dept = $query->find($department);

        if (!$dept) {
            return ApiResponse::error(
                ApiErrorCode::DEPARTMENT_NOT_FOUND,
                'Department was not found.',
                [],
                404
            );
        }

        if (!SiteAccess::canManageSite($request->user(), (int) $dept->site_id)) {
            return ApiResponse::error(
                ApiErrorCode::DEPARTMENT_SITE_ACCESS_DENIED,
                'You do not have permission to manage this department.',
                [],
                403
            );
        }

        $data = $request->validated();

        if (empty($data)) {
            $dept->load(['site:id,code,name,short_name,site_type,is_active',]);
            return new DepartmentResource($dept);
        }

        $candidateSiteId = array_key_exists('site_id', $data) ? (int) $data['site_id'] : (int) $dept->site_id;
        $candidateCode = $data['code'] ?? $dept->code;

        if ($candidateSiteId !== (int) $dept->site_id) {
            if (!SiteAccess::canManageSite($request->user(), $candidateSiteId)) {
                return ApiResponse::error(
                    ApiErrorCode::DEPARTMENT_SITE_ACCESS_DENIED,
                    'You do not have permission to move this department to the selected site.',
                    ['site_id' => $candidateSiteId,],
                    403
                );
            }

            if ($this->isDepartmentInUse($dept)) {
                return ApiResponse::error(
                    ApiErrorCode::DEPARTMENT_SITE_CHANGE_BLOCKED,
                    'This department cannot be moved to another site because it is already referenced by users, projects, or agreements.',
                    [
                        'department_id' => (int) $dept->id,
                        'from_site_id' => (int) $dept->site_id,
                        'to_site_id' => $candidateSiteId,
                    ],
                    409
                );
            }
        }

        $duplicate = Department::query()
            ->where('site_id', $candidateSiteId)
            ->where('code', $candidateCode)
            ->where('id', '!=', $dept->id)
            ->exists();

        if ($duplicate) {
            return ApiResponse::error(
                ApiErrorCode::DEPARTMENT_DUPLICATE_CODE,
                'A department with the same code already exists for this site.',
                [
                    'site_id' => $candidateSiteId,
                    'code' => $candidateCode,
                ],
                409
            );
        }

        if (array_key_exists('site_id', $data)) {
            $data['site_id'] = $candidateSiteId;
        }

        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = (bool) $data['is_active'];
        }

        $old = $dept->getOriginal();
        $dept->fill($data);

        if (!$dept->isDirty()) {
            $dept->load(['site:id,code,name,short_name,site_type,is_active',]);
            return new DepartmentResource($dept);
        }

        $dirty = $dept->getDirty();

        try {
            $dept->save();
            $changes = \App\Support\AuditDiff::diff($old, $dirty);

            \App\Support\Audit::log(
                $request->user()->id,
                'DEPARTMENT',
                (int) $dept->id,
                'UPDATE',
                $changes
            );

            $dept->refresh()->load(['site:id,code,name,short_name,site_type,is_active',]);

            return new DepartmentResource($dept);
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                ApiErrorCode::DEPARTMENT_UPDATE_FAILED,
                'Failed to update department.',
                $this->errorDetails($e),
                500
            );
        }
    }

    public function destroy(Request $request, $department)
    {
        $query = Department::query();
        SiteAccess::applyViewScope(
            $query,
            $request->user(),
            'lt_departments.site_id'
        );

        $dept = $query->find($department);

        if (!$dept) {
            return ApiResponse::error(
                ApiErrorCode::DEPARTMENT_NOT_FOUND,
                'Department was not found.',
                [],
                404
            );
        }

        if (!SiteAccess::canManageSite($request->user(), (int) $dept->site_id)) {
            return ApiResponse::error(
                ApiErrorCode::DEPARTMENT_SITE_ACCESS_DENIED,
                'You do not have permission to manage this department.',
                [],
                403
            );
        }

        $inUse = $this->isDepartmentInUse($dept);

        if ($inUse) {
            $from = (bool) $dept->is_active;

            if (!$from) {
                return response()->json([
                    'ok' => true,
                    'mode' => 'SOFT',
                    'message' => 'Department is already inactive.',
                ]);
            }

            try {
                $dept->update(['is_active' => false,]);

                \App\Support\Audit::log(
                    $request->user()->id,
                    'DEPARTMENT',
                    (int) $dept->id,
                    'DELETE',
                    [
                        'mode' => 'SOFT',
                        'reason' => 'Department is referenced by existing users, projects, or agreements.',
                        'snapshot' => [
                            'site_id' => (int) $dept->site_id,
                            'code' => $dept->code,
                            'name' => $dept->name,
                            'is_active' => $from,
                        ],
                        'changes' => [
                            'is_active' => [
                                'from' => $from ? 1 : 0,
                                'to' => 0,
                            ],
                        ],
                    ]
                );

                return response()->json([
                    'ok' => true,
                    'mode' => 'SOFT',
                ]);
            } catch (Throwable $e) {
                report($e);

                return ApiResponse::error(
                    ApiErrorCode::DEPARTMENT_DELETE_FAILED,
                    'Failed to deactivate department.',
                    $this->errorDetails($e),
                    500
                );
            }
        }

        $snapshot = [
            'site_id' => (int) $dept->site_id,
            'code' => $dept->code,
            'name' => $dept->name,
            'is_active' => (bool) $dept->is_active,
        ];

        $id = (int) $dept->id;

        try {
            $dept->delete();

            \App\Support\Audit::log(
                $request->user()->id,
                'DEPARTMENT',
                $id,
                'DELETE',
                [
                    'mode' => 'HARD',
                    'snapshot' => $snapshot,
                ]
            );

            return response()->json([
                'ok' => true,
                'mode' => 'HARD',
            ]);
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                ApiErrorCode::DEPARTMENT_DELETE_FAILED,
                'Failed to delete department.',
                $this->errorDetails($e),
                500
            );
        }
    }

    private function isDepartmentInUse(Department $department): bool
    {
        $departmentId = (int) $department->id;
        return User::query()
            ->where('department_id', $departmentId)
            ->exists()
            || Project::query()
            ->where('department_id', $departmentId)
            ->exists()
            || Agreement::query()
            ->where('department_id', $departmentId)
            ->exists();
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
