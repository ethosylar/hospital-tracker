<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExternalSourceRequest;
use App\Http\Requests\UpdateExternalSourceRequest;
use App\Http\Resources\ExternalSourceResource;
use App\Models\ExternalSource;
use App\Support\ApiErrorCode;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ExternalSourceController extends Controller
{
    public function index(Request $request)
    {
        $q = ExternalSource::query()
            ->select(['id','code','name','base_url','is_active','created_at','updated_at']);

        if ($request->filled('is_active')) {
            $q->where('is_active', (int)$request->is_active);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $q->where(function ($w) use ($s) {
                $w->where('code', 'like', "%{$s}%")
                  ->orWhere('name', 'like', "%{$s}%")
                  ->orWhere('base_url', 'like', "%{$s}%");
            });
        }

        $perPage = max(1, min((int)$request->get('per_page', 50), 100));

        return ExternalSourceResource::collection(
            $q->orderBy('name')->paginate($perPage)
        );
    }

    public function show(ExternalSource $source)
    {
        return new ExternalSourceResource($source);
    }

    public function store(StoreExternalSourceRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = array_key_exists('is_active', $data) ? (int)$data['is_active'] : 1;

        try {
            $source = ExternalSource::create($data);

            \App\Support\Audit::log(
                $request->user()->id,
                'EXTERNAL_SOURCE',
                (int)$source->id,
                'CREATE',
                $data
            );

            return (new ExternalSourceResource($source))
                ->response()
                ->setStatusCode(201);

        } catch (\Throwable $e) {
            report($e);
            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_SOURCE_CREATE_FAILED,
                'Failed to create external source',
                500
            );
        }
    }

    public function update(UpdateExternalSourceRequest $request, ExternalSource $source)
    {
        $data = $request->validated();

        if (empty($data)) {
            return response()->json(['ok' => true, 'message' => 'No changes']);
        }

        $old = $source->getOriginal();
        $changes = \App\Support\AuditDiff::diff($old, $data);

        if (empty($changes)) {
            return response()->json(['ok' => true, 'message' => 'No changes']);
        }

        try {
            $source->update($data);

            \App\Support\Audit::log(
                $request->user()->id,
                'EXTERNAL_SOURCE',
                (int)$source->id,
                'UPDATE',
                $changes
            );

            return new ExternalSourceResource($source->refresh());

        } catch (\Throwable $e) {
            report($e);
            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_SOURCE_UPDATE_FAILED,
                'Failed to update external source',
                500
            );
        }
    }

    public function destroy(Request $request, ExternalSource $source)
    {
        // Safe default: always SOFT delete for lookup tables
        $before = $source->replicate();

        try {
            $source->update(['is_active' => false]);

            \App\Support\Audit::log(
                $request->user()->id,
                'EXTERNAL_SOURCE',
                (int)$source->id,
                'DELETE',
                [
                    'mode' => 'SOFT',
                    'snapshot' => [
                        'code' => $before->code,
                        'name' => $before->name,
                        'base_url' => $before->base_url,
                        'is_active' => (int)$before->is_active,
                    ],
                    'changes' => [
                        'is_active' => ['from' => (int)$before->is_active, 'to' => 0],
                    ],
                ]
            );

            return response()->json(['ok' => true, 'mode' => 'SOFT']);

        } catch (\Throwable $e) {
            report($e);
            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_SOURCE_DELETE_FAILED,
                'Failed to delete external source',
                500
            );
        }
    }
}
