<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExternalSourceRequest;
use App\Http\Requests\UpdateExternalSourceRequest;
use App\Http\Resources\ExternalSourceResource;
use App\Models\ExternalSource;
use App\Support\ApiErrorCode;
use App\Support\ApiResponse;
use App\Support\SiteAccess;
use Illuminate\Http\Request;
use Throwable;

class ExternalSourceController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'site_id' => ['nullable','integer','exists:lt_sites,id',],
            'is_active' => ['nullable','boolean',],
            'search' => ['nullable','string','max:255',],
            'per_page' => ['nullable','integer','min:1','max:100',],
        ]);

        $query = ExternalSource::query()->with(['site:id,code,name,short_name',]);

        SiteAccess::applyViewScope($query, $request->user(), 'lt_external_sources.site_id');

        if ($request->filled('site_id')) {
            $query->where('lt_external_sources.site_id', (int) $request->site_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(
                function ($where) use ($search) {
                    $where
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('base_url', 'like', "%{$search}%");
                }
            );
        }

        $perPage = max(1, min((int) $request->get('per_page', 50), 100));

        return ExternalSourceResource::collection(
            $query
                ->orderBy('site_id')
                ->orderBy('name')
                ->paginate($perPage)
        );
    }

    public function show(Request $request, ExternalSource $source)
    {
        if (!SiteAccess::canViewSite($request->user(), (int) $source->site_id)) {
            abort(404);
        }

        $source->load(['site:id,code,name,short_name',]);
        return new ExternalSourceResource($source);
    }

    public function store(StoreExternalSourceRequest $request)
    {
        $data = $request->validated();
        $siteId = (int) $data['site_id'];

        if (!SiteAccess::canManageSite($request->user(), $siteId)) {
            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_SOURCE_SITE_ACCESS_DENIED,
                'You do not have management access to this Site.',
                [],
                403
            );
        }

        $data['is_active'] = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true;

        try {
            $source = ExternalSource::create($data);

            \App\Support\Audit::log(
                $request->user()->id,
                'EXTERNAL_SOURCE',
                (int) $source->id,
                'CREATE',
                $source->only([
                    'site_id',
                    'code',
                    'name',
                    'base_url',
                    'is_active',
                ])
            );

            $source->load(['site:id,code,name,short_name',]);

            return (new ExternalSourceResource($source))
                ->response()
                ->setStatusCode(201);
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_SOURCE_CREATE_FAILED,
                'Failed to create external source.',
                $this->errorDetails($e),
                500
            );
        }
    }

    public function update(UpdateExternalSourceRequest $request, ExternalSource $source)
    {
        $data = $request->validated();
        $user = $request->user();

        if (!SiteAccess::canManageSite($user, (int) $source->site_id)) {
            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_SOURCE_SITE_ACCESS_DENIED,
                'You do not have management access to this External Source Site.',
                [],
                403
            );
        }

        $candidateSiteId = array_key_exists('site_id', $data) ? (int) $data['site_id'] : (int) $source->site_id;

        if ($candidateSiteId !== (int) $source->site_id) {
            if (!SiteAccess::canManageSite($user, $candidateSiteId)) {
                return ApiResponse::error(
                    ApiErrorCode::EXTERNAL_SOURCE_SITE_ACCESS_DENIED,
                    'You do not have management access to the destination Site.',
                    [],
                    403
                );
            }

            /*
             * Once the source owns synchronized records,
             * its Site becomes immutable.
             */
            if ($source->permits()->exists() || $source->syncRuns()->exists() || $source->riskIssues()->exists()) {
                return ApiResponse::error(
                    ApiErrorCode::EXTERNAL_SOURCE_SITE_LOCKED,
                    'The External Source Site cannot be changed after dependent records exist.',
                    [],
                    422
                );
            }
        }

        if (empty($data)) {
            return response()->json([
                'ok' => true,
                'message' => 'No changes',
            ]);
        }

        $changes = \App\Support\AuditDiff::diff(
            $source->getOriginal(),
            $data
        );

        if (empty($changes)) {
            return new ExternalSourceResource(
                $source->load(
                    'site:id,code,name,short_name'
                )
            );
        }

        try {
            $source->update(
                $data
            );

            \App\Support\Audit::log(
                $user->id,
                'EXTERNAL_SOURCE',
                (int) $source->id,
                'UPDATE',
                $changes
            );

            return new ExternalSourceResource(
                $source
                    ->refresh()
                    ->load([
                        'site:id,code,name,short_name',
                    ])
            );
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_SOURCE_UPDATE_FAILED,
                'Failed to update external source.',
                $this->errorDetails($e),
                500
            );
        }
    }

    public function destroy(Request $request, ExternalSource $source)
    {
        if (!SiteAccess::canManageSite($request->user(), (int) $source->site_id)) {
            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_SOURCE_SITE_ACCESS_DENIED,
                'You do not have management access to this Site.',
                [],
                403
            );
        }

        $before = $source->replicate();

        try {
            $source->update([
                'is_active' => false,
            ]);

            \App\Support\Audit::log(
                $request->user()->id,
                'EXTERNAL_SOURCE',
                (int) $source->id,
                'DELETE',
                [
                    'mode' => 'SOFT',
                    'snapshot' => [
                        'site_id' => (int) $before->site_id,
                        'code' => $before->code,
                        'name' => $before->name,
                        'base_url' => $before->base_url,
                        'is_active' => (bool) $before->is_active,
                    ],

                    'changes' => [
                        'is_active' => ['from' => true, 'to' => false,],
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
                ApiErrorCode::EXTERNAL_SOURCE_DELETE_FAILED,
                'Failed to deactivate external source.',
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
