<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskIssueTypeRequest;
use App\Http\Requests\UpdateRiskIssueTypeRequest;
use App\Http\Resources\RiskIssueTypeResource;
use App\Models\RiskIssueType;
use App\Support\ApiErrorCode;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class RiskIssueTypeController extends Controller
{
    public function index(Request $request)
    {
        $q = RiskIssueType::query()->select(['id','code','name','is_active','created_at','updated_at']);

        if ($request->filled('is_active')) {
            $q->where('is_active', (int) $request->is_active);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $q->where(function ($w) use ($s) {
                $w->where('code', 'like', "%{$s}%")
                  ->orWhere('name', 'like', "%{$s}%");
            });
        }

        $perPage = max(1, min((int) $request->get('per_page', 50), 100));

        return RiskIssueTypeResource::collection(
            $q->orderBy('code')->orderBy('name')->paginate($perPage)
        );
    }

    public function show(RiskIssueType $type)
    {
        return new RiskIssueTypeResource($type);
    }

    public function store(StoreRiskIssueTypeRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = array_key_exists('is_active', $data) ? (int)$data['is_active'] : 1;

        try {
            $type = RiskIssueType::create($data);

            \App\Support\Audit::log(
                $request->user()->id,
                'RISK_ISSUE_TYPE',
                (int)$type->id,
                'CREATE',
                $data
            );

            return (new RiskIssueTypeResource($type))
                ->response()
                ->setStatusCode(201);

        } catch (\Throwable $e) {
            report($e);
            return ApiResponse::error(
                ApiErrorCode::RISK_ISSUE_TYPE_CREATE_FAILED,
                'Failed to create risk/issue type',
                500
            );
        }
    }

    public function update(UpdateRiskIssueTypeRequest $request, RiskIssueType $type)
    {
        $data = $request->validated();

        if (empty($data)) {
            return response()->json(['ok' => true, 'message' => 'No changes']);
        }

        $old = $type->getOriginal();
        $changes = \App\Support\AuditDiff::diff($old, $data);

        if (empty($changes)) {
            return response()->json(['ok' => true, 'message' => 'No changes']);
        }

        try {
            $type->update($data);

            \App\Support\Audit::log(
                $request->user()->id,
                'RISK_ISSUE_TYPE',
                (int)$type->id,
                'UPDATE',
                $changes
            );

            return new RiskIssueTypeResource($type->refresh());

        } catch (\Throwable $e) {
            report($e);
            return ApiResponse::error(
                ApiErrorCode::RISK_ISSUE_TYPE_UPDATE_FAILED,
                'Failed to update risk/issue type',
                500
            );
        }
    }

    public function destroy(Request $request, RiskIssueType $type)
    {
        // Safe default: always soft delete for lookup data
        $before = $type->replicate(); // snapshot

        try {
            $type->update(['is_active' => false]);

            \App\Support\Audit::log(
                $request->user()->id,
                'RISK_ISSUE_TYPE',
                (int)$type->id,
                'DELETE',
                [
                    'mode' => 'SOFT',
                    'snapshot' => [
                        'code' => $before->code,
                        'name' => $before->name,
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
                ApiErrorCode::RISK_ISSUE_TYPE_DELETE_FAILED,
                'Failed to delete risk/issue type',
                500
            );
        }
    }
}
