<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExternalRiskIssueLinkRequest;
use App\Http\Requests\StoreExternalRiskIssueRequest;
use App\Http\Requests\UpdateExternalRiskIssueRequest;
use App\Http\Resources\ExternalRiskIssueResource;
use App\Models\ExternalPermit;
use App\Models\ExternalRiskIssue;
use App\Models\ExternalRiskIssueLink;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Support\ApiErrorCode;
use App\Support\ApiResponse;
use App\Support\ExternalRiskIssueAccess;
use App\Support\ExternalRiskIssueSiteRules;
use App\Support\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ExternalRiskIssueController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Common eager loading
    |--------------------------------------------------------------------------
    */

    private function withLookups($query, bool $withLinks = false)
    {
        $query->with([
            'site:id,code,name,short_name',
            'externalSource:id,site_id,code,name',
            'project:id,site_id,code,name',
            'type:id,code,name',
            'severity:id,code,name',
            'status:id,code,name',
        ]);

        if ($withLinks) {
            $query->with([
                'activeLinks' =>
                function ($linkQuery) {
                    $linkQuery
                        ->with([
                            'project:id,site_id,code,name',
                            'task:id,project_id,milestone_id,title,name',
                            'milestone:id,project_id,name,milestone_date',
                            'permit:id,site_id,external_form_id,external_permit_id,normalized_status,is_source_deleted',
                            'linkedBy:id,name,email',
                        ])
                        ->orderByDesc('id');
                },
            ]);
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $data = $request->validate([
            'site_id' => ['nullable', 'integer', 'exists:lt_sites,id',],
            'external_source_id' => ['nullable', 'integer', 'exists:lt_external_sources,id',],
            'project_id' => ['nullable', 'integer', 'exists:dt_projects,id',],
            'task_id' => ['nullable', 'integer', 'exists:dt_project_tasks,id',],
            'milestone_id' => ['nullable', 'integer', 'exists:dt_project_milestones,id',],
            'permit_id' => ['nullable', 'integer', 'exists:dt_external_permits,id',],
            'type_id' => ['nullable', 'integer', 'exists:lt_risk_issue_types,id',],
            'severity_id' => ['nullable', 'integer', 'exists:st_severities,id',],
            'risk_issue_status_id' => ['nullable', 'integer', 'exists:st_risk_issue_statuses,id',],
            'search' => ['nullable', 'string', 'max:255',],
            'source_updated_from' => ['nullable', 'date',],
            'source_updated_to' => ['nullable', 'date',],
            'include_links' => ['nullable', 'boolean',],
            'page' => ['nullable', 'integer', 'min:1',],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100',],
        ]);

        $includeLinks = (bool) ($data['include_links'] ?? false);

        /*
        |--------------------------------------------------------------------------
        | Start from Site-authorized population
        |--------------------------------------------------------------------------
        */

        $query = $this->withLookups(ExternalRiskIssueAccess::visibleQuery($request->user()), $includeLinks);

        /*
        |--------------------------------------------------------------------------
        | Explicit Site filter
        |--------------------------------------------------------------------------
        */

        if (!empty($data['site_id'])) {
            $query->where('dt_external_risk_issues.site_id', (int) $data['site_id']);
        }

        foreach (['external_source_id', 'type_id', 'severity_id', 'risk_issue_status_id',] as $field) {
            if (!empty($data[$field])) {
                $query->where($field, (int) $data[$field]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Project
        |--------------------------------------------------------------------------
        |
        | Existing behaviour is preserved:
        |
        | direct project_id
        | OR an active link to the Project
        |--------------------------------------------------------------------------
        */

        if (!empty($data['project_id'])) {
            $projectId =
                (int) $data['project_id'];

            $query->where(
                function ($where) use ($projectId) {
                    $where
                        ->where('project_id', $projectId)
                        ->orWhereHas('activeLinks', fn($linkQuery) => $linkQuery->where('project_id', $projectId));
                }
            );
        }

        if (!empty($data['task_id'])) {
            $taskId = (int) $data['task_id'];
            $query->whereHas('activeLinks', fn($linkQuery) => $linkQuery->where('task_id', $taskId));
        }

        if (!empty($data['milestone_id'])) {
            $milestoneId = (int) $data['milestone_id'];
            $query->whereHas('activeLinks', fn($linkQuery) => $linkQuery->where('milestone_id', $milestoneId));
        }

        if (!empty($data['permit_id'])) {
            $permitId = (int) $data['permit_id'];
            $query->whereHas('activeLinks', fn($linkQuery) => $linkQuery->where('permit_id', $permitId));
        }

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (!empty($data['search'])) {
            $search = trim($data['search']);
            $query->where(
                function ($where) use ($search) {
                    $where
                        ->where('external_id', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('owner', 'like', "%{$search}%");
                }
            );
        }

        if (!empty($data['source_updated_from'])) {
            $query->where('source_updated_at', '>=', $data['source_updated_from']);
        }

        if (!empty($data['source_updated_to'])) {
            $query->where('source_updated_at', '<=', $data['source_updated_to']);
        }

        $perPage = max(1, min((int) ($data['per_page'] ?? 50), 100));

        return ExternalRiskIssueResource::collection(
            $query
                ->orderByDesc('source_updated_at')
                ->orderByDesc('updated_at')
                ->paginate($perPage)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(Request $request, ExternalRiskIssue $issue)
    {
        /*
         * risk-issue.site middleware performs
         * primary authorization.
         */

        return new ExternalRiskIssueResource($this->issueWithLinks((int) $issue->id));
    }

    /*
    |--------------------------------------------------------------------------
    | Project
    |--------------------------------------------------------------------------
    */

    public function projectIndex(Request $request, Project $project)
    {
        if (!ProjectAccess::canView($request->user(), $project)) {
            abort(404);
        }

        $request->merge([
            'site_id' => (int) $project->site_id,
            'project_id' => (int) $project->id,
        ]);

        return $this->index($request);
    }

    /*
    |--------------------------------------------------------------------------
    | Task
    |--------------------------------------------------------------------------
    */

    public function taskIndex(Request $request, ProjectTask $task)
    {
        $project = Project::query()
            ->findOrFail($task->project_id);

        if (!ProjectAccess::canView($request->user(), $project)) {
            abort(404);
        }

        $request->merge([
            'site_id' => (int) $project->site_id,
            'task_id' => (int) $task->id,
        ]);

        return $this->index($request);
    }

    /*
    |--------------------------------------------------------------------------
    | Milestone
    |--------------------------------------------------------------------------
    */

    public function milestoneIndex(Request $request, Project $project, ProjectMilestone $milestone)
    {
        if ((int) $milestone->project_id !== (int) $project->id) {
            abort(404);
        }

        if (!ProjectAccess::canView($request->user(), $project)) {
            abort(404);
        }

        $request->merge([
            'site_id' => (int) $project->site_id,
            'project_id' => (int) $project->id,
            'milestone_id' => (int) $milestone->id,
        ]);

        return $this->index($request);
    }

    /*
    |--------------------------------------------------------------------------
    | ePTW Permit
    |--------------------------------------------------------------------------
    */

    public function permitIndex(Request $request, ExternalPermit $permit)
    {
        /*
         * permit.site middleware already protects this,
         * but include Site filter as defense-in-depth.
         */

        $request->merge([
            'site_id' => (int) $permit->site_id,
            'permit_id' => (int) $permit->id,
        ]);

        return $this->index($request);
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(StoreExternalRiskIssueRequest $request)
    {
        $data = $request->validated();
        $user = $request->user();
        $siteId = (int) $data['site_id'];

        /*
        |--------------------------------------------------------------------------
        | Site MANAGE permission
        |--------------------------------------------------------------------------
        */

        if (!ExternalRiskIssueAccess::canManageSite($user, $siteId)) {
            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_RISK_ISSUE_SITE_ACCESS_DENIED,
                'You do not have management access to the selected Site.',
                [],
                403
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Source / Project Site consistency
        |--------------------------------------------------------------------------
        */

        ExternalRiskIssueSiteRules::validate(
            $siteId,
            isset($data['external_source_id'])
                ? (int) $data['external_source_id']
                : null,
            isset($data['project_id'])
                ? (int) $data['project_id']
                : null
        );

        /*
        |--------------------------------------------------------------------------
        | Raw payload
        |--------------------------------------------------------------------------
        */

        $rawPayloadSha = null;

        $payloadError = $this->normalizeRawPayload($data, $rawPayloadSha);

        if ($payloadError) {
            return $payloadError;
        }

        /*
        |--------------------------------------------------------------------------
        | Source external ID uniqueness
        |--------------------------------------------------------------------------
        */

        if (!empty($data['external_source_id'])) {
            $duplicate = ExternalRiskIssue::query()
                ->where('external_source_id', (int) $data['external_source_id'])
                ->where('external_id', $data['external_id'])
                ->exists();

            if ($duplicate) {
                return ApiResponse::error(
                    ApiErrorCode::EXTERNAL_RISK_ISSUE_DUPLICATE_EXTERNAL_ID,
                    'Duplicate external_id for this external_source_id.',
                    [],
                    409
                );
            }
        }

        try {
            $issue = ExternalRiskIssue::create($data);

            \App\Support\Audit::log(
                $user->id,
                'EXTERNAL_RISK_ISSUE',
                (int) $issue->id,
                'CREATE',
                [
                    'site_id' => (int) $issue->site_id,
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

            return (new ExternalRiskIssueResource($this->issueWithLinks((int) $issue->id)))
                ->response()
                ->setStatusCode(201);
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_RISK_ISSUE_CREATE_FAILED,
                'Failed to create external risk issue.',
                $this->errorDetails($e),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(UpdateExternalRiskIssueRequest $request, ExternalRiskIssue $issue)
    {
        $data = $request->validated();
        $user = $request->user();

        if (empty($data)) {
            return new ExternalRiskIssueResource(
                $this->issueWithLinks(
                    (int) $issue->id
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Current Site MANAGE
        |--------------------------------------------------------------------------
        */

        if (!ExternalRiskIssueAccess::canManage($user, $issue)) {
            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_RISK_ISSUE_SITE_ACCESS_DENIED,
                'You do not have management access to this Risk/Issue Site.',
                [],
                403
            );
        }

        $candidateSiteId = array_key_exists('site_id', $data) ? (int) $data['site_id'] : (int) $issue->site_id;

        /*
        |--------------------------------------------------------------------------
        | Destination Site MANAGE
        |--------------------------------------------------------------------------
        */

        if ($candidateSiteId !== (int) $issue->site_id) {
            if (!ExternalRiskIssueAccess::canManageSite($user, $candidateSiteId)) {
                return ApiResponse::error(
                    ApiErrorCode::EXTERNAL_RISK_ISSUE_SITE_ACCESS_DENIED,
                    'You do not have management access to the destination Site.',
                    [],
                    403
                );
            }

            /*
             * Historical links must retain one stable Site.
             */
            if ($issue->links()->exists()) {
                return ApiResponse::error(
                    ApiErrorCode::EXTERNAL_RISK_ISSUE_SITE_LOCKED,
                    'The Site cannot be changed after the Risk/Issue has link history. Remove/recreate the record instead.',
                    [],
                    422
                );
            }
        }

        $candidateSourceId = array_key_exists('external_source_id', $data)
            ? ($data['external_source_id'] !== null ? (int) $data['external_source_id'] : null)
            : ($issue->external_source_id ? (int) $issue->external_source_id : null);

        $candidateProjectId = array_key_exists('project_id', $data)
            ? ($data['project_id'] !== null ? (int) $data['project_id'] : null)
            : ($issue->project_id ? (int) $issue->project_id : null);

        ExternalRiskIssueSiteRules::validate($candidateSiteId, $candidateSourceId, $candidateProjectId);

        /*
        |--------------------------------------------------------------------------
        | Raw payload
        |--------------------------------------------------------------------------
        */

        $oldPayloadSha = $issue->raw_payload !== null ? sha1((string) $issue->raw_payload) : null;
        $newPayloadSha = $oldPayloadSha;
        $payloadError = $this->normalizeRawPayload($data, $newPayloadSha);

        if ($payloadError) {
            return $payloadError;
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate external ID
        |--------------------------------------------------------------------------
        */

        $candidateExternalId = array_key_exists('external_id', $data) ? $data['external_id'] : $issue->external_id;

        if ($candidateSourceId !== null) {
            $duplicate = ExternalRiskIssue::query()
                ->where('external_source_id', $candidateSourceId)
                ->where('external_id', $candidateExternalId)
                ->where('id', '!=', $issue->id)
                ->exists();

            if ($duplicate) {
                return ApiResponse::error(
                    ApiErrorCode::EXTERNAL_RISK_ISSUE_DUPLICATE_EXTERNAL_ID,
                    'Duplicate external_id for this external_source_id.',
                    [],
                    409
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Audit diff
        |--------------------------------------------------------------------------
        */

        $old = $issue->getOriginal();
        $oldForDiff = $old;
        unset($oldForDiff['raw_payload']);
        $dataForDiff = $data;
        unset($dataForDiff['raw_payload']);
        $changes = \App\Support\AuditDiff::diff($oldForDiff, $dataForDiff);

        if (array_key_exists('raw_payload', $data) && $oldPayloadSha !== $newPayloadSha) {
            $changes['raw_payload_sha1'] = [
                'from' => $oldPayloadSha,
                'to' => $newPayloadSha,
            ];
        }

        if (empty($changes)) {
            return new ExternalRiskIssueResource(
                $this->issueWithLinks(
                    (int) $issue->id
                )
            );
        }

        try {
            $issue->update($data);
            \App\Support\Audit::log(
                $user->id,
                'EXTERNAL_RISK_ISSUE',
                (int) $issue->id,
                'UPDATE',
                $changes
            );

            return new ExternalRiskIssueResource($this->issueWithLinks((int) $issue->id));
        } catch (Throwable $e) {
            report($e);
            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_RISK_ISSUE_UPDATE_FAILED,
                'Failed to update external risk issue.',
                $this->errorDetails($e),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function destroy(Request $request, ExternalRiskIssue $issue)
    {
        if (!ExternalRiskIssueAccess::canManage($request->user(), $issue)) {
            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_RISK_ISSUE_SITE_ACCESS_DENIED,
                'You do not have management access to this Risk/Issue Site.',
                [],
                403
            );
        }

        $snapshot = [
            'site_id' => (int) $issue->site_id,
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
                (int) $issue->id,
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
                ApiErrorCode::EXTERNAL_RISK_ISSUE_DELETE_FAILED,
                'Failed to delete external risk issue.',
                $this->errorDetails($e),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Link
    |--------------------------------------------------------------------------
    */

    public function link(StoreExternalRiskIssueLinkRequest $request, ExternalRiskIssue $issue)
    {
        if (!ExternalRiskIssueAccess::canManage($request->user(), $issue)) {
            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_RISK_ISSUE_SITE_ACCESS_DENIED,
                'You do not have management access to this Risk/Issue Site.',
                [],
                403
            );
        }

        $data = $request->validated();

        try {
            /*
             * Resolves Project from Task/Milestone/Permit
             * and enforces one Site.
             */
            $data = $this->normalizeLinkData($data, $issue);

            /*
            |--------------------------------------------------------------------------
            | Duplicate active link
            |--------------------------------------------------------------------------
            */

            $duplicate = ExternalRiskIssueLink::query()
                ->where('external_risk_issue_id', $issue->id)
                ->where('is_active', true);

            foreach (['project_id', 'task_id', 'milestone_id', 'permit_id',] as $field) {
                $this->whereNullable($duplicate, $field, $data[$field] ?? null);
            }

            if ($duplicate->exists()) {
                return ApiResponse::error(
                    ApiErrorCode::EXTERNAL_RISK_ISSUE_DUPLICATE_LINK,
                    'This external risk issue link already exists.',
                    [],
                    409
                );
            }

            $link = DB::transaction(
                function () use (
                    $request,
                    $issue,
                    $data
                ) {
                    return ExternalRiskIssueLink::create([
                        'external_risk_issue_id' => (int) $issue->id,
                        'project_id' => $data['project_id'] ?? null,
                        'task_id' => $data['task_id'] ?? null,
                        'milestone_id' => $data['milestone_id'] ?? null,
                        'permit_id' => $data['permit_id'] ?? null,
                        'linked_by_user_id' => $request->user()->id,
                        'linked_at' => now(),
                        'notes' => $data['notes'] ?? null,
                        'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
                    ]);
                }
            );

            \App\Support\Audit::log(
                $request->user()->id,
                'EXTERNAL_RISK_ISSUE_LINK',
                (int) $link->id,
                'LINK',
                [
                    'site_id' => (int) $issue->site_id,
                    'external_risk_issue_id' => (int) $issue->id,
                    'project_id' => $link->project_id,
                    'task_id' => $link->task_id,
                    'milestone_id' => $link->milestone_id,
                    'permit_id' => $link->permit_id,
                    'notes' => $link->notes,
                ]
            );

            return (new ExternalRiskIssueResource($this->issueWithLinks((int) $issue->id)))
                ->response()
                ->setStatusCode(201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_RISK_ISSUE_LINK_FAILED,
                'Failed to link external risk issue.',
                $this->errorDetails($e),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Unlink
    |--------------------------------------------------------------------------
    */

    public function unlink(Request $request, ExternalRiskIssue $issue, ExternalRiskIssueLink $link)
    {
        if (!ExternalRiskIssueAccess::canManage($request->user(), $issue)) {
            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_RISK_ISSUE_SITE_ACCESS_DENIED,
                'You do not have management access to this Risk/Issue Site.',
                [],
                403
            );
        }

        if ((int) $link->external_risk_issue_id !== (int) $issue->id) {
            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_RISK_ISSUE_LINK_NOT_FOUND,
                'External risk issue link was not found for this issue.',
                [],
                404
            );
        }

        if (!$link->is_active) {
            return response()->json([
                'ok' => true,
                'message' =>
                'Link already inactive.',
            ]);
        }

        try {
            $snapshot = $link->only(['project_id', 'task_id', 'milestone_id', 'permit_id', 'notes',]);
            $link->update(['is_active' => false,]);

            \App\Support\Audit::log(
                $request->user()->id,
                'EXTERNAL_RISK_ISSUE_LINK',
                (int) $link->id,
                'UNLINK',
                [
                    'site_id' => (int) $issue->site_id,
                    'external_risk_issue_id' => (int) $issue->id,
                    'snapshot' => $snapshot,
                ]
            );

            return response()->json([
                'ok' => true,
            ]);
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                ApiErrorCode::EXTERNAL_RISK_ISSUE_UNLINK_FAILED,
                'Failed to unlink external risk issue.',
                $this->errorDetails($e),
                500
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Load detailed Risk/Issue
    |--------------------------------------------------------------------------
    */

    private function issueWithLinks(int $issueId): ExternalRiskIssue
    {
        return $this->withLookups(ExternalRiskIssue::query()->whereKey($issueId), true)->firstOrFail();
    }

    /*
    |--------------------------------------------------------------------------
    | Raw JSON normalization
    |--------------------------------------------------------------------------
    */

    private function normalizeRawPayload(array &$data, ?string &$payloadSha)
    {
        if (!array_key_exists('raw_payload', $data)) {
            return null;
        }

        if (is_array($data['raw_payload'])) {
            $encoded = json_encode($data['raw_payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $data['raw_payload'] = $encoded;
            $payloadSha = $encoded ? sha1($encoded) : null;
            return null;
        }

        if (is_string($data['raw_payload'])) {
            json_decode($data['raw_payload'], true);

            if ($data['raw_payload'] !== '' && json_last_error() !== JSON_ERROR_NONE) {
                return ApiResponse::error(
                    ApiErrorCode::EXTERNAL_RISK_ISSUE_INVALID_RAW_PAYLOAD,
                    'raw_payload must be valid JSON.',
                    [],
                    422
                );
            }

            $payloadSha = $data['raw_payload'] ? sha1($data['raw_payload']) : null;
            return null;
        }

        if ($data['raw_payload'] === null) {
            $payloadSha = null;
            return null;
        }

        return ApiResponse::error(
            ApiErrorCode::EXTERNAL_RISK_ISSUE_INVALID_RAW_PAYLOAD,
            'raw_payload must be a JSON object/array, JSON string, or null.',
            [],
            422
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Link normalization + Site integrity
    |--------------------------------------------------------------------------
    */

    private function normalizeLinkData(array $data, ExternalRiskIssue $issue): array
    {
        $siteId = (int) $issue->site_id;
        $projectId = !empty($data['project_id']) ? (int) $data['project_id'] : null;
        /*
        |--------------------------------------------------------------------------
        | Explicit Project
        |--------------------------------------------------------------------------
        */

        if ($projectId !== null) {
            $project = Project::query()
                ->whereKey($projectId)
                ->where('site_id', $siteId)
                ->first();

            if (!$project) {
                throw ValidationException::withMessages([
                    'project_id' => [
                        'The selected Project must belong to the same Site as the Risk/Issue.',
                    ],
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Task -> Project
        |--------------------------------------------------------------------------
        */

        if (!empty($data['task_id'])) {
            $task = ProjectTask::query()->findOrFail((int) $data['task_id']);
            $taskProject = Project::query()->findOrFail($task->project_id);

            if ((int) $taskProject->site_id !== $siteId) {
                throw ValidationException::withMessages([
                    'task_id' => ['The selected Task belongs to another Site.',],
                ]);
            }

            if ($projectId !== null && (int) $task->project_id !== $projectId) {
                throw ValidationException::withMessages([
                    'task_id' => [
                        'The selected Task does not belong to the selected Project.',
                    ],
                ]);
            }

            $projectId = (int) $task->project_id;
        }

        /*
        |--------------------------------------------------------------------------
        | Milestone -> Project
        |--------------------------------------------------------------------------
        */

        if (!empty($data['milestone_id'])) {
            $milestone = ProjectMilestone::query()->findOrFail((int) $data['milestone_id']);
            $milestoneProject = Project::query()->findOrFail($milestone->project_id);

            if ((int) $milestoneProject->site_id !== $siteId) {
                throw ValidationException::withMessages([
                    'milestone_id' => [
                        'The selected Milestone belongs to another Site.',
                    ],
                ]);
            }

            if ($projectId !== null && (int) $milestone->project_id !== $projectId) {
                throw ValidationException::withMessages([
                    'milestone_id' => [
                        'The selected Milestone does not belong to the selected Project/Task Project.',
                    ],
                ]);
            }

            $projectId = (int) $milestone->project_id;
        }

        /*
        |--------------------------------------------------------------------------
        | ePTW Permit
        |--------------------------------------------------------------------------
        */

        if (!empty($data['permit_id'])) {
            $permit = ExternalPermit::query()->findOrFail((int) $data['permit_id']);

            if ((int) $permit->site_id !== $siteId) {
                throw ValidationException::withMessages([
                    'permit_id' => [
                        'The selected ePTW Permit belongs to another Site.',
                    ],
                ]);
            }

            if ($permit->is_source_deleted) {
                throw ValidationException::withMessages([
                    'permit_id' => [
                        'A deleted ePTW Permit cannot be linked.',
                    ],
                ]);
            }

            /*
             * Resolve active Project association from Permit.
             */
            $permitProjectIds =
                DB::table('dt_project_permit_links')
                ->where('permit_id', $permit->id)
                ->where('is_active', true)
                ->whereNotNull('project_id')
                ->distinct()
                ->pluck('project_id')
                ->map(fn($id) => (int) $id);

            if ($permitProjectIds->count() > 1) {
                throw ValidationException::withMessages([
                    'permit_id' => [
                        'The selected ePTW Permit is linked to multiple Projects and cannot be resolved safely.',
                    ],
                ]);
            }

            if ($permitProjectIds->count() === 1) {
                $permitProjectId = (int) $permitProjectIds->first();

                if ($projectId !== null && $permitProjectId !== $projectId) {
                    throw ValidationException::withMessages([
                        'permit_id' => [
                            'The selected ePTW Permit is linked to a different Project.',
                        ],
                    ]);
                }
                $projectId = $permitProjectId;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Final Project Site check
        |--------------------------------------------------------------------------
        */

        if ($projectId !== null) {
            $projectOk = Project::query()
                ->whereKey($projectId)
                ->where('site_id', $siteId)
                ->exists();

            if (!$projectOk) {
                throw ValidationException::withMessages([
                    'project_id' => [
                        'The resolved Project belongs to another Site.',
                    ],
                ]);
            }
        }

        $data['project_id'] = $projectId;
        $data['task_id'] = !empty($data['task_id']) ? (int) $data['task_id'] : null;
        $data['milestone_id'] = !empty($data['milestone_id']) ? (int) $data['milestone_id'] : null;
        $data['permit_id'] = !empty($data['permit_id']) ? (int) $data['permit_id'] : null;
        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Nullable query helper
    |--------------------------------------------------------------------------
    */

    private function whereNullable($query, string $column, mixed $value): void
    {
        if ($value === null) {
            $query->whereNull($column);
            return;
        }

        $query->where($column, $value);
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
