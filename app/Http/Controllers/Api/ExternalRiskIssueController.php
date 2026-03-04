<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExternalRiskIssueRequest;
use App\Http\Requests\UpdateExternalRiskIssueRequest;
use App\Http\Resources\ExternalRiskIssueResource;
use App\Models\ExternalRiskIssue;
use App\Support\ApiErrorCode;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ExternalRiskIssueController extends Controller
{
    private function withLookups($q)
    {
        return $q->with([
            'externalSource:id,code,name',
            'project:id,code,name',
            'type:id,code,name',
            'severity:id,code,name',
            'status:id,code,name',
        ]);
    }

    public function index(Request $request)
    {
        $q = $this->withLookups(
            ExternalRiskIssue::query()
        );

        foreach (['project_id','external_source_id','type_id','severity_id','risk_issue_status_id'] as $f) {
            if ($request->filled($f)) {
                $q->where($f, (int)$request->get($f));
            }
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $q->where(function ($w) use ($s) {
                $w->where('external_id', 'like', "%{$s}%")
                  ->orWhere('title', 'like', "%{$s}%")
                  ->orWhere('owner', 'like', "%{$s}%");
            });
        }

        if ($request->filled('source_updated_from')) {
            $q->where('source_updated_at', '>=', $request->source_updated_from);
        }
        if ($request->filled('source_updated_to')) {
            $q->where('source_updated_at', '<=', $request->source_updated_to);
        }

        $perPage = max(1, min((int)$request->get('per_page', 50), 100));

        $p = $q->orderByDesc('source_updated_at')
               ->orderByDesc('updated_at')
               ->paginate($perPage);

        return ExternalRiskIssueResource::collection($p);
    }

    public function show(Request $request, ExternalRiskIssue $issue)
    {
        $issue = $this->withLookups(
            ExternalRiskIssue::query()->whereKey($issue->id)
        )->firstOrFail();

        // resource supports include_payload=1
        return new ExternalRiskIssueResource($issue);
    }

    public function store(StoreExternalRiskIssueRequest $request)
    {
        $data = $request->validated();

        // raw_payload: accept array/object OR JSON string OR null
        $rawPayloadSha = null;

        if (array_key_exists('raw_payload', $data)) {
            if (is_array($data['raw_payload'])) {
                $encoded = json_encode($data['raw_payload'], JSON_UNESCAPED_UNICODE);
                $data['raw_payload'] = $encoded;
                $rawPayloadSha = $encoded ? sha1($encoded) : null;
            } elseif (is_string($data['raw_payload'])) {
                $try = json_decode($data['raw_payload'], true);
                if ($data['raw_payload'] !== '' && json_last_error() !== JSON_ERROR_NONE) {
                    return ApiResponse::error(
                        ApiErrorCode::EXTERNAL_RISK_ISSUE_INVALID_RAW_PAYLOAD,
                        'raw_payload must be valid JSON',
                        422
                    );
                }
                $rawPayloadSha = $data['raw_payload'] ? sha1($data['raw_payload']) : null;
            } elseif ($data['raw_payload'] === null) {
                $rawPayloadSha = null;
            } else {
                return ApiResponse::error(
                    ApiErrorCode::EXTERNAL_RISK_ISSUE_INVALID_RAW_PAYLOAD,
                    'raw_payload must be JSON object/array or JSON string',
                    422
                );
            }
        }

        // composite unique only when external_source_id is not null
        if (!empty($data['external_source_id'])) {
            $dup = ExternalRiskIssue::query()
                ->where('external_source_id', (int)$data['external_source_id'])
                ->where('external_id', $data['external_id'])
                ->exists();

            if ($dup) {
                return ApiResponse::error(
                    ApiErrorCode::EXTERNAL_RISK_ISSUE_DUPLICATE_EXTERNAL_ID,
                    'Duplicate external_id for this external_source_id',
                    409
                );
            }
        }

        try {
            $issue = ExternalRiskIssue::create($data);

            \App\Support\Audit::log(
                $request->user()->id,
                'EXTERNAL_RISK_ISSUE',
                (int)$issue->id,
                'CREATE',
                [
                    'external_source_id' => $issue->external_source_id,
                    'external_id' => $issue->external_id,
                    'project_id' => $issue->project_id,
                    'type_id' => $issue->type_id,
                    'title' => $issue->title,
                    'severity_id' => $issue->severity_id,
                    'risk_issue_status_id' => $issue->risk_issue_status_id,
                    'owner' => $issue->owner,
                    'source_created_at' => $issue->source_created_at,
                    'source_updated_at' => $issue->source_updated_at,
                    'last_synced_at' => $issue->last_synced_at,
                    'raw_payload_sha1' => $rawPayloadSha,
                ]
            );

            return (new ExternalRiskIssueResource(
                $this->withLookups(ExternalRiskIssue::query())->findOrFail($issue->id)
            ))->response()->setStatusCode(201);

        } catch (\Throwable $e) {
            report($e);
            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_RISK_ISSUE_CREATE_FAILED,
                'Failed to create external risk issue',
                500
            );
        }
    }

    public function update(UpdateExternalRiskIssueRequest $request, ExternalRiskIssue $issue)
    {
        $data = $request->validated();

        if (empty($data)) {
            return response()->json(['ok' => true, 'message' => 'No changes']);
        }

        // payload hash changes (avoid logging payload content)
        $oldPayloadSha = $issue->raw_payload !== null ? sha1((string)$issue->raw_payload) : null;
        $newPayloadSha = $oldPayloadSha;

        if (array_key_exists('raw_payload', $data)) {
            if (is_array($data['raw_payload'])) {
                $encoded = json_encode($data['raw_payload'], JSON_UNESCAPED_UNICODE);
                $data['raw_payload'] = $encoded;
                $newPayloadSha = $encoded ? sha1($encoded) : null;
            } elseif (is_string($data['raw_payload'])) {
                $try = json_decode($data['raw_payload'], true);
                if ($data['raw_payload'] !== '' && json_last_error() !== JSON_ERROR_NONE) {
                    return ApiResponse::error(
                        ApiErrorCode::EXTERNAL_RISK_ISSUE_INVALID_RAW_PAYLOAD,
                        'raw_payload must be valid JSON',
                        422
                    );
                }
                $newPayloadSha = $data['raw_payload'] ? sha1($data['raw_payload']) : null;
            } elseif ($data['raw_payload'] === null) {
                $newPayloadSha = null;
            } else {
                return ApiResponse::error(
                    ApiErrorCode::EXTERNAL_RISK_ISSUE_INVALID_RAW_PAYLOAD,
                    'raw_payload must be JSON object/array or JSON string',
                    422
                );
            }
        }

        // composite unique check (based on resulting values)
        $candidateSourceId = array_key_exists('external_source_id', $data) ? $data['external_source_id'] : $issue->external_source_id;
        $candidateExternalId = array_key_exists('external_id', $data) ? $data['external_id'] : $issue->external_id;

        if (!empty($candidateSourceId)) {
            $dup = ExternalRiskIssue::query()
                ->where('external_source_id', (int)$candidateSourceId)
                ->where('external_id', $candidateExternalId)
                ->where('id', '!=', (int)$issue->id)
                ->exists();

            if ($dup) {
                return ApiResponse::error(
                    ApiErrorCode::EXTERNAL_RISK_ISSUE_DUPLICATE_EXTERNAL_ID,
                    'Duplicate external_id for this external_source_id',
                    409
                );
            }
        }

        // Audit diff (exclude raw_payload, add hash change)
        $old = $issue->getOriginal();
        $oldForDiff = $old;
        unset($oldForDiff['raw_payload']);

        $dataForDiff = $data;
        unset($dataForDiff['raw_payload']);

        $changes = \App\Support\AuditDiff::diff($oldForDiff, $dataForDiff);

        if (array_key_exists('raw_payload', $data) && $oldPayloadSha !== $newPayloadSha) {
            $changes['raw_payload_sha1'] = ['from' => $oldPayloadSha, 'to' => $newPayloadSha];
        }

        if (empty($changes)) {
            return response()->json(['ok' => true, 'message' => 'No changes']);
        }

        try {
            $issue->update($data);

            \App\Support\Audit::log(
                $request->user()->id,
                'EXTERNAL_RISK_ISSUE',
                (int)$issue->id,
                'UPDATE',
                $changes
            );

            return new ExternalRiskIssueResource(
                $this->withLookups(ExternalRiskIssue::query())->findOrFail($issue->id)
            );

        } catch (\Throwable $e) {
            report($e);
            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_RISK_ISSUE_UPDATE_FAILED,
                'Failed to update external risk issue',
                500
            );
        }
    }

    public function destroy(Request $request, ExternalRiskIssue $issue)
    {
        $snapshot = [
            'external_source_id' => $issue->external_source_id,
            'external_id' => $issue->external_id,
            'project_id' => $issue->project_id,
            'type_id' => $issue->type_id,
            'title' => $issue->title,
            'severity_id' => $issue->severity_id,
            'risk_issue_status_id' => $issue->risk_issue_status_id,
            'owner' => $issue->owner,
            'source_updated_at' => $issue->source_updated_at,
            'last_synced_at' => $issue->last_synced_at,
        ];

        try {
            $issue->delete();

            \App\Support\Audit::log(
                $request->user()->id,
                'EXTERNAL_RISK_ISSUE',
                (int)$issue->id,
                'DELETE',
                [
                    'mode' => 'HARD',
                    'snapshot' => $snapshot,
                ]
            );

            return response()->json(['ok' => true, 'mode' => 'HARD']);

        } catch (\Throwable $e) {
            report($e);
            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_RISK_ISSUE_DELETE_FAILED,
                'Failed to delete external risk issue',
                500
            );
        }
    }
}
