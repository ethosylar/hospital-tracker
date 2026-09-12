<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteIndexRequest;
use App\Http\Requests\StoreSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
use App\Http\Resources\SiteResource;
use App\Models\Site;
use App\Support\ApiErrorCode;
use App\Support\ApiResponse;
use App\Support\SiteAccess;
use Illuminate\Http\Request;
use Throwable;

class SiteController extends Controller
{
    public function index(SiteIndexRequest $request)
    {
        $data = $request->validated();

        $query = Site::query();

        SiteAccess::applyViewScope(
            $query,
            $request->user(),
            'lt_sites.id'
        );

        if (!empty($data['search'])) {
            $search = $data['search'];

            $query->where(function ($where) use ($search) {
                $where
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%");
            });
        }

        if (!empty($data['site_type'])) {
            $query->where('site_type', $data['site_type']);
        }

        if (array_key_exists('is_active', $data)) {
            $query->where('is_active', (bool) $data['is_active']);
        }

        $perPage = max(
            1,
            min((int) ($data['per_page'] ?? 50), 100)
        );

        return SiteResource::collection(
            $query
                ->orderByDesc('is_active')
                ->orderBy('name')
                ->paginate($perPage)
        );
    }

    public function show(Request $request, Site $site)
    {
        if (!SiteAccess::canViewSite($request->user(), $site)) {
            return ApiResponse::error(
                ApiErrorCode::SITE_NOT_FOUND,
                'Site was not found.',
                [],
                404
            );
        }

        return new SiteResource($site);
    }

    public function store(StoreSiteRequest $request)
    {
        $data = $request->validated();

        if (
            Site::query()
                ->where('code', $data['code'])
                ->exists()
        ) {
            return ApiResponse::error(
                ApiErrorCode::SITE_DUPLICATE_CODE,
                'A site with the same code already exists.',
                [],
                409
            );
        }

        $data['country'] = $data['country'] ?? 'Malaysia';
        $data['is_active'] = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : true;

        try {
            $site = Site::create($data);

            \App\Support\Audit::log(
                $request->user()->id,
                'SITE',
                (int) $site->id,
                'CREATE',
                $this->auditSnapshot($site)
            );

            return (new SiteResource($site))
                ->response()
                ->setStatusCode(201);
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                ApiErrorCode::SITE_CREATE_FAILED,
                'Failed to create site.',
                $this->errorDetails($e),
                500
            );
        }
    }

    public function update(
        UpdateSiteRequest $request,
        Site $site
    ) {
        $data = $request->validated();

        if (empty($data)) {
            return new SiteResource($site);
        }

        if (
            array_key_exists('code', $data)
            && Site::query()
                ->where('code', $data['code'])
                ->where('id', '!=', $site->id)
                ->exists()
        ) {
            return ApiResponse::error(
                ApiErrorCode::SITE_DUPLICATE_CODE,
                'A site with the same code already exists.',
                [],
                409
            );
        }

        $old = $site->getOriginal();
        $site->fill($data);

        if (!$site->isDirty()) {
            return new SiteResource($site);
        }

        $dirty = $site->getDirty();

        try {
            $site->save();

            $changes = \App\Support\AuditDiff::diff(
                $old,
                $dirty
            );

            \App\Support\Audit::log(
                $request->user()->id,
                'SITE',
                (int) $site->id,
                'UPDATE',
                $changes
            );

            return new SiteResource($site->refresh());
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                ApiErrorCode::SITE_UPDATE_FAILED,
                'Failed to update site.',
                $this->errorDetails($e),
                500
            );
        }
    }

    /**
     * Site records are deactivated instead of physically deleted.
     */
    public function destroy(Request $request, Site $site)
    {
        if (!$site->is_active) {
            return response()->json([
                'ok' => true,
                'mode' => 'SOFT',
                'message' => 'Site is already inactive.',
            ]);
        }

        try {
            $site->update(['is_active' => false]);

            \App\Support\Audit::log(
                $request->user()->id,
                'SITE',
                (int) $site->id,
                'DELETE',
                [
                    'mode' => 'SOFT',
                    'changes' => [
                        'is_active' => [
                            'from' => 1,
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
                ApiErrorCode::SITE_DELETE_FAILED,
                'Failed to deactivate site.',
                $this->errorDetails($e),
                500
            );
        }
    }

    private function auditSnapshot(Site $site): array
    {
        return [
            'code' => $site->code,
            'name' => $site->name,
            'short_name' => $site->short_name,
            'site_type' => $site->site_type,
            'city' => $site->city,
            'state' => $site->state,
            'country' => $site->country,
            'is_active' => (int) $site->is_active,
        ];
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