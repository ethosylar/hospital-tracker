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
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Validation\ValidationException;
	use Throwable;
	
	class ExternalRiskIssueController extends Controller
	{
		private function withLookups($q, bool $withLinks = false)
		{
			$q->with([
            'externalSource:id,code,name',
            'project:id,code,name',
            'type:id,code,name',
            'severity:id,code,name',
            'status:id,code,name',
			]);
			
			if ($withLinks) {
				$q->with([
                'activeLinks' => function ($l) {
                    $l->with([
					'project:id,code,name',
					'task:id,project_id,milestone_id,title,name',
					'milestone:id,project_id,name,milestone_date',
					'permit:id,external_form_id,external_permit_id,normalized_status',
					'linkedBy:id,name,email',
                    ])->orderByDesc('id');
				},
				]);
			}
			
			return $q;
		}
		
		public function index(Request $request)
		{
			$includeLinks = $request->boolean('include_links', false);
			$q = $this->withLookups(ExternalRiskIssue::query(), $includeLinks);
			
			foreach (['external_source_id', 'type_id', 'severity_id', 'risk_issue_status_id'] as $f) {
				if ($request->filled($f)) {
					$q->where($f, (int) $request->get($f));
				}
			}
			
			if ($request->filled('project_id')) {
				$projectId = (int) $request->get('project_id');
				$q->where(function ($w) use ($projectId) {
					$w->where('project_id', $projectId)
                    ->orWhereHas('activeLinks', fn ($l) => $l->where('project_id', $projectId));
				});
			}
			
			if ($request->filled('task_id')) {
				$taskId = (int) $request->get('task_id');
				$q->whereHas('activeLinks', fn ($l) => $l->where('task_id', $taskId));
			}
			
			if ($request->filled('milestone_id')) {
				$milestoneId = (int) $request->get('milestone_id');
				$q->whereHas('activeLinks', fn ($l) => $l->where('milestone_id', $milestoneId));
			}
			
			if ($request->filled('permit_id')) {
				$permitId = (int) $request->get('permit_id');
				$q->whereHas('activeLinks', fn ($l) => $l->where('permit_id', $permitId));
			}
			
			if ($request->filled('search')) {
				$s = trim((string) $request->search);
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
			
			$perPage = max(1, min((int) $request->get('per_page', 50), 100));
			
			$p = $q->orderByDesc('source_updated_at')
            ->orderByDesc('updated_at')
            ->paginate($perPage);
			
			return ExternalRiskIssueResource::collection($p);
		}
		
		public function show(Request $request, ExternalRiskIssue $issue)
		{
			$issue = $this->issueWithLinks((int) $issue->id);
			return new ExternalRiskIssueResource($issue);
		}
		
		public function projectIndex(Request $request, Project $project)
		{
			$request->merge(['project_id' => (int) $project->id]);
			return $this->index($request);
		}
		
		public function taskIndex(Request $request, ProjectTask $task)
		{
			$request->merge(['task_id' => (int) $task->id]);
			return $this->index($request);
		}
		
		public function milestoneIndex(Request $request, Project $project, ProjectMilestone $milestone)
		{
			if ((int) $milestone->project_id !== (int) $project->id) {
				abort(404);
			}
			
			$request->merge([
            'project_id' => (int) $project->id,
            'milestone_id' => (int) $milestone->id,
			]);
			
			return $this->index($request);
		}
		
		public function permitIndex(Request $request, ExternalPermit $permit)
		{
			$request->merge(['permit_id' => (int) $permit->id]);
			return $this->index($request);
		}
		
		public function store(StoreExternalRiskIssueRequest $request)
		{
			$data = $request->validated();
			$rawPayloadSha = null;
			
			$payloadError = $this->normalizeRawPayload($data, $rawPayloadSha);
			if ($payloadError) {
				return $payloadError;
			}
			
			if (!empty($data['external_source_id'])) {
				$dup = ExternalRiskIssue::query()
                ->where('external_source_id', (int) $data['external_source_id'])
                ->where('external_id', $data['external_id'])
                ->exists();
				
				if ($dup) {
					return ApiResponse::error(
                    ApiErrorCode::EXTERNAL_RISK_ISSUE_DUPLICATE_EXTERNAL_ID,
                    'Duplicate external_id for this external_source_id',
                    [],
                    409
					);
				}
			}
			
			try {
				$issue = ExternalRiskIssue::create($data);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'EXTERNAL_RISK_ISSUE',
                (int) $issue->id,
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
				
				return (new ExternalRiskIssueResource($this->issueWithLinks((int) $issue->id)))
                ->response()
                ->setStatusCode(201);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::EXTERNAL_RISK_ISSUE_CREATE_FAILED,
                'Failed to create external risk issue',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function update(UpdateExternalRiskIssueRequest $request, ExternalRiskIssue $issue)
		{
			$data = $request->validated();
			
			if (empty($data)) {
				return new ExternalRiskIssueResource($this->issueWithLinks((int) $issue->id));
			}
			
			$oldPayloadSha = $issue->raw_payload !== null ? sha1((string) $issue->raw_payload) : null;
			$newPayloadSha = $oldPayloadSha;
			
			$payloadError = $this->normalizeRawPayload($data, $newPayloadSha);
			if ($payloadError) {
				return $payloadError;
			}
			
			$candidateSourceId = array_key_exists('external_source_id', $data)
            ? $data['external_source_id']
            : $issue->external_source_id;
			
			$candidateExternalId = array_key_exists('external_id', $data)
            ? $data['external_id']
            : $issue->external_id;
			
			if (!empty($candidateSourceId)) {
				$dup = ExternalRiskIssue::query()
                ->where('external_source_id', (int) $candidateSourceId)
                ->where('external_id', $candidateExternalId)
                ->where('id', '!=', (int) $issue->id)
                ->exists();
				
				if ($dup) {
					return ApiResponse::error(
                    ApiErrorCode::EXTERNAL_RISK_ISSUE_DUPLICATE_EXTERNAL_ID,
                    'Duplicate external_id for this external_source_id',
                    [],
                    409
					);
				}
			}
			
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
				return new ExternalRiskIssueResource($this->issueWithLinks((int) $issue->id));
			}
			
			try {
				$issue->update($data);
				
				\App\Support\Audit::log(
                $request->user()->id,
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
                'Failed to update external risk issue',
                $this->errorDetails($e),
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
                (int) $issue->id,
                'DELETE',
                [
				'mode' => 'HARD',
				'snapshot' => $snapshot,
                ]
				);
				
				return response()->json(['ok' => true, 'mode' => 'HARD']);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::EXTERNAL_RISK_ISSUE_DELETE_FAILED,
                'Failed to delete external risk issue',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function link(StoreExternalRiskIssueLinkRequest $request, ExternalRiskIssue $issue)
		{
			$data = $request->validated();
			
			try {
				$data = $this->normalizeLinkData($data);
				
				$duplicate = ExternalRiskIssueLink::query()
                ->where('external_risk_issue_id', (int) $issue->id)
                ->where('is_active', true);
				
				foreach (['project_id', 'task_id', 'milestone_id', 'permit_id'] as $field) {
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
				
				$link = DB::transaction(function () use ($request, $issue, $data) {
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
				});
				
				\App\Support\Audit::log(
                $request->user()->id,
                'EXTERNAL_RISK_ISSUE_LINK',
                (int) $link->id,
                'LINK',
                [
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
		
		public function unlink(Request $request, ExternalRiskIssue $issue, ExternalRiskIssueLink $link)
		{
			if ((int) $link->external_risk_issue_id !== (int) $issue->id) {
				return ApiResponse::error(
                ApiErrorCode::EXTERNAL_RISK_ISSUE_LINK_NOT_FOUND,
                'External risk issue link was not found for this issue.',
                [],
                404
				);
			}
			
			if (!$link->is_active) {
				return response()->json(['ok' => true, 'message' => 'Link already inactive.']);
			}
			
			try {
				$snapshot = $link->only(['project_id', 'task_id', 'milestone_id', 'permit_id', 'notes']);
				$link->update(['is_active' => false]);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'EXTERNAL_RISK_ISSUE_LINK',
                (int) $link->id,
                'UNLINK',
                [
				'external_risk_issue_id' => (int) $issue->id,
				'snapshot' => $snapshot,
                ]
				);
				
				return response()->json(['ok' => true]);
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
		
		private function issueWithLinks(int $issueId): ExternalRiskIssue
		{
			return $this->withLookups(ExternalRiskIssue::query()->whereKey($issueId), true)
            ->firstOrFail();
		}
		
		private function normalizeRawPayload(array &$data, ?string &$payloadSha)
		{
			if (!array_key_exists('raw_payload', $data)) {
				return null;
			}
			
			if (is_array($data['raw_payload'])) {
				$encoded = json_encode($data['raw_payload'], JSON_UNESCAPED_UNICODE);
				$data['raw_payload'] = $encoded;
				$payloadSha = $encoded ? sha1($encoded) : null;
				return null;
			}
			
			if (is_string($data['raw_payload'])) {
				json_decode($data['raw_payload'], true);
				if ($data['raw_payload'] !== '' && json_last_error() !== JSON_ERROR_NONE) {
					return ApiResponse::error(
                    ApiErrorCode::EXTERNAL_RISK_ISSUE_INVALID_RAW_PAYLOAD,
                    'raw_payload must be valid JSON',
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
            'raw_payload must be JSON object/array or JSON string',
            [],
            422
			);
		}
		
		private function normalizeLinkData(array $data): array
		{
			$projectId = !empty($data['project_id']) ? (int) $data['project_id'] : null;
			
			$task = null;
			if (!empty($data['task_id'])) {
				$task = ProjectTask::query()->findOrFail((int) $data['task_id']);
				
				if ($projectId && (int) $task->project_id !== $projectId) {
					throw ValidationException::withMessages([
                    'task_id' => ['The selected task does not belong to the selected project.'],
					]);
				}
				
				$projectId = (int) $task->project_id;
			}
			
			if (!empty($data['milestone_id'])) {
				$milestone = ProjectMilestone::query()->findOrFail((int) $data['milestone_id']);
				
				if ($projectId && (int) $milestone->project_id !== $projectId) {
					throw ValidationException::withMessages([
                    'milestone_id' => ['The selected milestone does not belong to the selected project/task project.'],
					]);
				}
				
				$projectId = (int) $milestone->project_id;
			}
			
			if (!empty($data['permit_id'])) {
				$permitId = (int) $data['permit_id'];
				
				$permitProjectId = DB::table('dt_project_permit_links')
                ->where('permit_id', $permitId)
                ->where('is_active', true)
                ->value('project_id');
				
				if ($permitProjectId) {
					if ($projectId && (int) $permitProjectId !== $projectId) {
						throw ValidationException::withMessages([
                        'permit_id' => ['The selected ePTW permit is linked to a different project.'],
						]);
					}
					
					$projectId = (int) $permitProjectId;
				}
			}
			
			$data['project_id'] = $projectId;
			$data['task_id'] = !empty($data['task_id']) ? (int) $data['task_id'] : null;
			$data['milestone_id'] = !empty($data['milestone_id']) ? (int) $data['milestone_id'] : null;
			$data['permit_id'] = !empty($data['permit_id']) ? (int) $data['permit_id'] : null;
			
			return $data;
		}
		
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
