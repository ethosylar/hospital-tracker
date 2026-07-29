<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\AgreementIndexRequest;
	use App\Http\Requests\AgreementNotesRequest;
	use App\Http\Requests\AmendAgreementRequest;
	use App\Http\Requests\LinkAgreementProjectRequest;
	use App\Http\Requests\RenewAgreementRequest;
	use App\Http\Requests\StoreAgreementRequest;
	use App\Http\Requests\TerminateAgreementRequest;
	use App\Http\Requests\UpdateAgreementRequest;
	use App\Http\Resources\AgreementResource;
	use App\Models\Agreement;
	use App\Models\AgreementLifecycleEvent;
	use App\Models\AgreementProjectLink;
	use App\Models\AgreementStatus;
	use App\Models\AgreementType;
	use App\Support\ApiErrorCode;
	use App\Support\ApiResponse;
	use Carbon\Carbon;
	use Illuminate\Database\Eloquent\Builder;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	use RuntimeException;
	use Throwable;
	
	class AgreementController extends Controller
	{
		public function index(AgreementIndexRequest $request)
		{
			$data = $request->validated();
			
			$query = $this->withSummaryRelations(
            Agreement::query()
			);
			
			$this->applyVisibilityScope($query, $request);
			
			foreach ([
            'department_id',
            'owner_user_id',
            'counterparty_id',
            'agreement_category_id',
            'agreement_type_id',
            'agreement_status_id',
			] as $field) {
				if (array_key_exists($field, $data)) {
					$query->where($field, (int) $data[$field]);
				}
			}
			
			if (!empty($data['status_code'])) {
				$query->whereHas(
                'status',
                fn ($statusQuery) => $statusQuery->where(
				'code',
				strtoupper($data['status_code'])
                )
				);
			}
			
			if (!empty($data['lifecycle_type'])) {
				$query->where(
                'lifecycle_type',
                $data['lifecycle_type']
				);
			}
			
			if (array_key_exists('is_current_version', $data)) {
				$query->where(
                'is_current_version',
                (bool) $data['is_current_version']
				);
			}
			
			if (!empty($data['effective_from'])) {
				$query->whereDate(
                'effective_date',
                '>=',
                $data['effective_from']
				);
			}
			
			if (!empty($data['effective_to'])) {
				$query->whereDate(
                'effective_date',
                '<=',
                $data['effective_to']
				);
			}
			
			if (!empty($data['expiry_from'])) {
				$query->whereDate(
                'expiry_date',
                '>=',
                $data['expiry_from']
				);
			}
			
			if (!empty($data['expiry_to'])) {
				$query->whereDate(
                'expiry_date',
                '<=',
                $data['expiry_to']
				);
			}
			
			if (empty($data['include_archived'])) {
				$query->whereDoesntHave(
                'status',
                fn ($statusQuery) => $statusQuery->where(
				'code',
				'ARCHIVED'
                )
				);
			}
			
			if (!empty($data['search'])) {
				$search = $data['search'];
				
				$query->where(function ($where) use ($search) {
					$where
                    ->where('agreement_no', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas(
					'counterparty',
					fn ($counterpartyQuery) => $counterpartyQuery
					->where(
					'legal_name',
					'like',
					"%{$search}%"
					)
					->orWhere(
					'trading_name',
					'like',
					"%{$search}%"
					)
                    );
				});
			}
			
			$perPage = max(
            1,
            min((int) ($data['per_page'] ?? 50), 100)
			);
			
			return AgreementResource::collection(
            $query
			->orderByDesc('is_current_version')
			->orderByDesc('updated_at')
			->paginate($perPage)
			);
		}
		
		public function show(Request $request, Agreement $agreement)
		{
			if (!$this->canAccess($request, $agreement)) {
				return $this->accessDenied();
			}
			
			return new AgreementResource(
            $this->loadDetail($agreement)
			);
		}
		
		public function store(StoreAgreementRequest $request)
		{
			$data = $request->validated();
			$user = $request->user();
			
			if (!$this->canCreateForScope($request, $data)) {
				return $this->accessDenied(
                'You cannot create an agreement for the selected owner or department.'
				);
			}
			
			$typeError = $this->validateTypeCategory(
            (int) $data['agreement_category_id'],
            isset($data['agreement_type_id'])
			? (int) $data['agreement_type_id']
			: null
			);
			
			if ($typeError) {
				return $typeError;
			}
			
			$dateError = $this->validateDates(
            $data['effective_date'] ?? null,
            $data['expiry_date'] ?? null
			);
			
			if ($dateError) {
				return $dateError;
			}
			
			try {
				$agreement = DB::transaction(function () use ($data, $user) {
					$draftStatus = $this->statusByCode('DRAFT');
					
					$agreement = Agreement::create([
                    ...$data,
                    'agreement_status_id' => $draftStatus->id,
                    'lifecycle_type' => 'ORIGINAL',
                    'revision_no' => 0,
                    'renewal_sequence' => 0,
                    'is_current_version' => true,
                    'auto_renewal' =>
					(bool) ($data['auto_renewal'] ?? false),
                    'currency_code' =>
					$data['currency_code'] ?? 'MYR',
                    'created_by_user_id' => $user->id,
                    'updated_by_user_id' => $user->id,
					]);
					
					// The original agreement is its own root.
					$agreement->update([
                    'root_agreement_id' => $agreement->id,
					]);
					
					$this->recordEvent(
                    agreement: $agreement,
                    eventType: 'CREATE',
                    userId: (int) $user->id,
                    toStatusId: (int) $draftStatus->id,
                    metadata: [
					'lifecycle_type' => 'ORIGINAL',
                    ]
					);
					
					return $agreement;
				});
				
				\App\Support\Audit::log(
                $user->id,
                'AGREEMENT',
                (int) $agreement->id,
                'CREATE',
                $this->auditSnapshot($agreement)
				);
				
				return (new AgreementResource(
                $this->loadDetail($agreement)
				))->response()->setStatusCode(201);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_CREATE_FAILED,
                'Failed to create agreement.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function update(
        UpdateAgreementRequest $request,
        Agreement $agreement
		) {
			if (!$this->canAccess($request, $agreement)) {
				return $this->accessDenied();
			}
			
			$statusCode = $this->agreementStatusCode($agreement);
			
			if (!in_array($statusCode, ['DRAFT', 'UNDER_REVIEW'], true)) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_NOT_EDITABLE,
                'Only Draft or Under Review agreements can be edited directly. Create an amendment for an approved or active agreement.',
                [
				'current_status' => $statusCode,
                ],
                409
				);
			}
			
			$data = $request->validated();
			
			if (empty($data)) {
				return new AgreementResource(
                $this->loadDetail($agreement)
				);
			}
			
			$candidateCategoryId = (int) (
            $data['agreement_category_id']
            ?? $agreement->agreement_category_id
			);
			
			$candidateTypeId = array_key_exists(
            'agreement_type_id',
            $data
			)
            ? ($data['agreement_type_id'] !== null
			? (int) $data['agreement_type_id']
			: null)
            : $agreement->agreement_type_id;
			
			$typeError = $this->validateTypeCategory(
            $candidateCategoryId,
            $candidateTypeId
			);
			
			if ($typeError) {
				return $typeError;
			}
			
			$candidateEffectiveDate = array_key_exists(
            'effective_date',
            $data
			)
            ? $data['effective_date']
            : $agreement->effective_date?->format('Y-m-d');
			
			$candidateExpiryDate = array_key_exists(
            'expiry_date',
            $data
			)
            ? $data['expiry_date']
            : $agreement->expiry_date?->format('Y-m-d');
			
			$dateError = $this->validateDates(
            $candidateEffectiveDate,
            $candidateExpiryDate
			);
			
			if ($dateError) {
				return $dateError;
			}
			
			if (!$this->canUpdateScope($request, $agreement, $data)) {
				return $this->accessDenied(
                'You cannot transfer this agreement to the selected owner or department.'
				);
			}
			
			$data['updated_by_user_id'] = $request->user()->id;
			
			$changes = \App\Support\AuditDiff::diff(
            $agreement->getOriginal(),
            $data
			);
			
			if (empty($changes)) {
				return new AgreementResource(
                $this->loadDetail($agreement)
				);
			}
			
			try {
				DB::transaction(function () use (
                $agreement,
                $data,
                $request,
                $changes
				) {
					$agreement->update($data);
					
					$this->recordEvent(
                    agreement: $agreement,
                    eventType: 'UPDATE',
                    userId: (int) $request->user()->id,
                    fromStatusId: (int) $agreement->agreement_status_id,
                    toStatusId: (int) $agreement->agreement_status_id,
                    metadata: [
					'changes' => $changes,
                    ]
					);
				});
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT',
                (int) $agreement->id,
                'UPDATE',
                $changes
				);
				
				return new AgreementResource(
                $this->loadDetail($agreement->refresh())
				);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_UPDATE_FAILED,
                'Failed to update agreement.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function review(
        AgreementNotesRequest $request,
        Agreement $agreement
		) {
			return $this->simpleTransition(
            request: $request,
            agreement: $agreement,
            allowedFrom: ['DRAFT'],
            toCode: 'UNDER_REVIEW',
            eventType: 'REVIEW',
            errorCode: ApiErrorCode::AGREEMENT_REVIEW_FAILED,
            errorMessage: 'Failed to move agreement under review.'
			);
		}
		
		public function submit(
        AgreementNotesRequest $request,
        Agreement $agreement
		) {
			if (!$this->canAccess($request, $agreement)) {
				return $this->accessDenied();
			}
			
			$currentCode = $this->agreementStatusCode($agreement);
			
			if (!in_array($currentCode, ['DRAFT', 'UNDER_REVIEW'], true)) {
				return $this->invalidTransition(
                $currentCode,
                'PENDING_APPROVAL'
				);
			}
			
			try {
				$pending = $this->statusByCode('PENDING_APPROVAL');
				$fromStatusId = (int) $agreement->agreement_status_id;
				
				DB::transaction(function () use (
                $agreement,
                $pending,
                $request,
                $fromStatusId
				) {
					$agreement->update([
                    'agreement_status_id' => $pending->id,
                    'submitted_at' => now(),
                    'submitted_by_user_id' => $request->user()->id,
                    'updated_by_user_id' => $request->user()->id,
					]);
					
					$this->recordEvent(
                    agreement: $agreement,
                    eventType: 'SUBMIT',
                    userId: (int) $request->user()->id,
                    fromStatusId: $fromStatusId,
                    toStatusId: (int) $pending->id,
                    reason: $request->validated('reason'),
                    metadata: [
					'notes' => $request->validated('notes'),
                    ]
					);
				});
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT',
                (int) $agreement->id,
                'SUBMIT',
                [
				'status' => [
				'from' => $currentCode,
				'to' => 'PENDING_APPROVAL',
				],
                ]
				);
				
				return new AgreementResource(
                $this->loadDetail($agreement->refresh())
				);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_SUBMIT_FAILED,
                'Failed to submit agreement for approval.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function approve(
        AgreementNotesRequest $request,
        Agreement $agreement
		) {
			if (!$this->canAccess($request, $agreement)) {
				return $this->accessDenied();
			}
			
			$currentCode = $this->agreementStatusCode($agreement);
			
			if ($currentCode !== 'PENDING_APPROVAL') {
				return $this->invalidTransition(
                $currentCode,
                'APPROVED'
				);
			}
			
			try {
				$agreement = DB::transaction(function () use (
                $agreement,
                $request
				) {
					$approved = $this->statusByCode('APPROVED');
					$active = $this->statusByCode('ACTIVE');
					
					$target = (
                    $agreement->effective_date !== null
                    && $agreement->effective_date->lte(today())
					) ? $active : $approved;
					
					$fromStatusId = (int) $agreement->agreement_status_id;
					
					// Only the approved amendment/renewal becomes the current
					// version. Its predecessor remains current until this point.
					if (in_array(
                    $agreement->lifecycle_type,
                    ['AMENDMENT', 'RENEWAL'],
                    true
					)) {
						$rootId = $agreement->root_agreement_id
                        ?: $agreement->id;
						
						Agreement::query()
                        ->where(function ($query) use ($rootId) {
                            $query
							->where('id', $rootId)
							->orWhere('root_agreement_id', $rootId);
						})
                        ->where('id', '!=', $agreement->id)
                        ->update([
						'is_current_version' => false,
						'updated_by_user_id' =>
						$request->user()->id,
                        ]);
						
						if ($agreement->parentAgreement) {
							$parent = $agreement->parentAgreement;
							$parentFromStatusId =
                            (int) $parent->agreement_status_id;
							
							if ($agreement->lifecycle_type === 'RENEWAL') {
								$parentTarget = $this->statusByCode('RENEWED');
								$parentUpdate = [
                                'agreement_status_id' => $parentTarget->id,
                                'is_current_version' => false,
                                'updated_by_user_id' =>
								$request->user()->id,
								];
								$parentEvent = 'RENEWED_BY';
								} else {
								$parentTarget = $this->statusByCode('ARCHIVED');
								$parentUpdate = [
                                'agreement_status_id' => $parentTarget->id,
                                'is_current_version' => false,
                                'archived_at' => now(),
                                'archived_by_user_id' =>
								$request->user()->id,
                                'updated_by_user_id' =>
								$request->user()->id,
								];
								$parentEvent = 'SUPERSEDED_BY_AMENDMENT';
							}
							
							$parent->update($parentUpdate);
							
							$this->recordEvent(
                            agreement: $parent,
                            eventType: $parentEvent,
                            userId: (int) $request->user()->id,
                            fromStatusId: $parentFromStatusId,
                            toStatusId: (int) $parentTarget->id,
                            relatedAgreementId: (int) $agreement->id
							);
						}
					}
					
					$agreement->update([
                    'agreement_status_id' => $target->id,
                    'approved_at' => now(),
                    'approved_by_user_id' => $request->user()->id,
                    'is_current_version' => true,
                    'updated_by_user_id' => $request->user()->id,
					]);
					
					$this->recordEvent(
                    agreement: $agreement,
                    eventType: 'APPROVE',
                    userId: (int) $request->user()->id,
                    fromStatusId: $fromStatusId,
                    toStatusId: (int) $target->id,
                    reason: $request->validated('reason'),
                    metadata: [
					'notes' => $request->validated('notes'),
                    ]
					);
					
					return $agreement->refresh();
				});
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT',
                (int) $agreement->id,
                'APPROVE',
                [
				'status' => [
				'from' => $currentCode,
				'to' => $this->agreementStatusCode($agreement),
				],
                ]
				);
				
				return new AgreementResource(
                $this->loadDetail($agreement)
				);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_APPROVE_FAILED,
                'Failed to approve agreement.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function activate(
        AgreementNotesRequest $request,
        Agreement $agreement
		) {
			if (
            $agreement->effective_date !== null
            && $agreement->effective_date->gt(today())
			) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_INVALID_TRANSITION,
                'The agreement cannot be activated before its effective date.',
                [
				'effective_date' =>
				$agreement->effective_date->format('Y-m-d'),
                ],
                422
				);
			}
			
			return $this->simpleTransition(
            request: $request,
            agreement: $agreement,
            allowedFrom: ['APPROVED'],
            toCode: 'ACTIVE',
            eventType: 'ACTIVATE',
            errorCode: ApiErrorCode::AGREEMENT_ACTIVATE_FAILED,
            errorMessage: 'Failed to activate agreement.'
			);
		}
		
		public function amend(
        AmendAgreementRequest $request,
        Agreement $agreement
		) {
			if (!$this->canAccess($request, $agreement)) {
				return $this->accessDenied();
			}
			
			$currentCode = $this->agreementStatusCode($agreement);
			
			if (!in_array(
            $currentCode,
            ['APPROVED', 'ACTIVE', 'EXPIRING_SOON'],
            true
			)) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_INVALID_TRANSITION,
                'Only Approved, Active, or Expiring Soon agreements can be amended.',
                ['current_status' => $currentCode],
                409
				);
			}
			
			$data = $request->validated();
			$effectiveDate = $data['effective_date']
            ?? $agreement->effective_date?->format('Y-m-d');
			$expiryDate = $data['expiry_date']
            ?? $agreement->expiry_date?->format('Y-m-d');
			
			$dateError = $this->validateDates(
            $effectiveDate,
            $expiryDate
			);
			
			if ($dateError) {
				return $dateError;
			}
			
			try {
				$amendment = DB::transaction(function () use (
                $agreement,
                $request,
                $data,
                $effectiveDate,
                $expiryDate
				) {
					$draft = $this->statusByCode('DRAFT');
					
					$amendment = Agreement::create([
                    ...$this->copyableAgreementData($agreement),
                    'agreement_no' => Agreement::generateAgreementNo(),
                    'title' => $data['title']
					?? $agreement->title
					. ' - Amendment '
					. ((int) $agreement->revision_no + 1),
                    'agreement_status_id' => $draft->id,
                    'effective_date' => $effectiveDate,
                    'expiry_date' => $expiryDate,
                    'lifecycle_type' => 'AMENDMENT',
                    'parent_agreement_id' => $agreement->id,
                    'root_agreement_id' =>
					$agreement->root_agreement_id ?: $agreement->id,
                    'revision_no' =>
					(int) $agreement->revision_no + 1,
                    'renewal_sequence' =>
					(int) $agreement->renewal_sequence,
                    'is_current_version' => false,
                    'submitted_at' => null,
                    'submitted_by_user_id' => null,
                    'approved_at' => null,
                    'approved_by_user_id' => null,
                    'terminated_on' => null,
                    'termination_reason' => null,
                    'terminated_by_user_id' => null,
                    'archived_at' => null,
                    'archived_by_user_id' => null,
                    'created_by_user_id' => $request->user()->id,
                    'updated_by_user_id' => $request->user()->id,
					]);
					
					if ($request->boolean('copy_project_links', true)) {
						$this->copyProjectLinks(
                        $agreement,
                        $amendment,
                        (int) $request->user()->id
						);
					}
					
					$this->recordEvent(
                    agreement: $agreement,
                    eventType: 'AMENDMENT_CREATED',
                    userId: (int) $request->user()->id,
                    fromStatusId: (int) $agreement->agreement_status_id,
                    toStatusId: (int) $agreement->agreement_status_id,
                    relatedAgreementId: (int) $amendment->id,
                    reason: $data['amendment_reason']
					);
					
					$this->recordEvent(
                    agreement: $amendment,
                    eventType: 'CREATE_AMENDMENT',
                    userId: (int) $request->user()->id,
                    toStatusId: (int) $draft->id,
                    relatedAgreementId: (int) $agreement->id,
                    reason: $data['amendment_reason']
					);
					
					return $amendment;
				});
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT',
                (int) $amendment->id,
                'AMEND',
                [
				'source_agreement_id' => (int) $agreement->id,
				'reason' => $data['amendment_reason'],
                ]
				);
				
				return (new AgreementResource(
                $this->loadDetail($amendment)
				))->response()->setStatusCode(201);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_AMEND_FAILED,
                'Failed to create agreement amendment.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function renew(
        RenewAgreementRequest $request,
        Agreement $agreement
		) {
			if (!$this->canAccess($request, $agreement)) {
				return $this->accessDenied();
			}
			
			$currentCode = $this->agreementStatusCode($agreement);
			
			if (!in_array(
            $currentCode,
            ['ACTIVE', 'EXPIRING_SOON', 'EXPIRED'],
            true
			)) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_INVALID_TRANSITION,
                'Only Active, Expiring Soon, or Expired agreements can be renewed.',
                ['current_status' => $currentCode],
                409
				);
			}
			
			$data = $request->validated();
			
			if (
            $agreement->effective_date !== null
            && Carbon::parse($data['effective_date'])
			->lte($agreement->effective_date)
			) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_INVALID_DATES,
                'The renewal effective date must be after the current agreement effective date.',
                [
				'current_effective_date' =>
				$agreement->effective_date->format('Y-m-d'),
                ],
                422
				);
			}
			
			try {
				$renewal = DB::transaction(function () use (
                $agreement,
                $request,
                $data
				) {
					$draft = $this->statusByCode('DRAFT');
					$renewalSequence =
                    (int) $agreement->renewal_sequence + 1;
					
					$renewal = Agreement::create([
                    ...$this->copyableAgreementData($agreement),
                    'agreement_no' => Agreement::generateAgreementNo(),
                    'title' => $data['title']
					?? $agreement->title
					. ' - Renewal '
					. $renewalSequence,
                    'agreement_status_id' => $draft->id,
                    'effective_date' => $data['effective_date'],
                    'expiry_date' => $data['expiry_date'],
                    'contract_value' => array_key_exists(
					'contract_value',
					$data
                    )
					? $data['contract_value']
					: $agreement->contract_value,
                    'currency_code' => $data['currency_code']
					?? $agreement->currency_code,
                    'notice_period_days' => array_key_exists(
					'notice_period_days',
					$data
                    )
					? $data['notice_period_days']
					: $agreement->notice_period_days,
                    'auto_renewal' => array_key_exists(
					'auto_renewal',
					$data
                    )
					? (bool) $data['auto_renewal']
					: (bool) $agreement->auto_renewal,
                    'lifecycle_type' => 'RENEWAL',
                    'parent_agreement_id' => $agreement->id,
                    'root_agreement_id' =>
					$agreement->root_agreement_id ?: $agreement->id,
                    'revision_no' => 0,
                    'renewal_sequence' => $renewalSequence,
                    'is_current_version' => false,
                    'signed_date' => null,
                    'submitted_at' => null,
                    'submitted_by_user_id' => null,
                    'approved_at' => null,
                    'approved_by_user_id' => null,
                    'terminated_on' => null,
                    'termination_reason' => null,
                    'terminated_by_user_id' => null,
                    'archived_at' => null,
                    'archived_by_user_id' => null,
                    'created_by_user_id' => $request->user()->id,
                    'updated_by_user_id' => $request->user()->id,
					]);
					
					if ($request->boolean('copy_project_links', true)) {
						$this->copyProjectLinks(
                        $agreement,
                        $renewal,
                        (int) $request->user()->id
						);
					}
					
					$this->recordEvent(
                    agreement: $agreement,
                    eventType: 'RENEWAL_CREATED',
                    userId: (int) $request->user()->id,
                    fromStatusId: (int) $agreement->agreement_status_id,
                    toStatusId: (int) $agreement->agreement_status_id,
                    relatedAgreementId: (int) $renewal->id,
                    reason: $data['renewal_reason'] ?? null
					);
					
					$this->recordEvent(
                    agreement: $renewal,
                    eventType: 'CREATE_RENEWAL',
                    userId: (int) $request->user()->id,
                    toStatusId: (int) $draft->id,
                    relatedAgreementId: (int) $agreement->id,
                    reason: $data['renewal_reason'] ?? null
					);
					
					return $renewal;
				});
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT',
                (int) $renewal->id,
                'RENEW',
                [
				'source_agreement_id' => (int) $agreement->id,
				'effective_date' => $data['effective_date'],
				'expiry_date' => $data['expiry_date'],
                ]
				);
				
				return (new AgreementResource(
                $this->loadDetail($renewal)
				))->response()->setStatusCode(201);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_RENEW_FAILED,
                'Failed to create agreement renewal.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function terminate(
        TerminateAgreementRequest $request,
        Agreement $agreement
		) {
			if (!$this->canAccess($request, $agreement)) {
				return $this->accessDenied();
			}
			
			$currentCode = $this->agreementStatusCode($agreement);
			
			if (!in_array(
            $currentCode,
            ['APPROVED', 'ACTIVE', 'EXPIRING_SOON', 'EXPIRED'],
            true
			)) {
				return $this->invalidTransition(
                $currentCode,
                'TERMINATED'
				);
			}
			
			$data = $request->validated();
			
			try {
				$terminated = $this->statusByCode('TERMINATED');
				$fromStatusId = (int) $agreement->agreement_status_id;
				
				DB::transaction(function () use (
                $agreement,
                $terminated,
                $request,
                $data,
                $fromStatusId
				) {
					$agreement->update([
                    'agreement_status_id' => $terminated->id,
                    'terminated_on' =>
					$data['terminated_on'] ?? today(),
                    'termination_reason' =>
					$data['termination_reason'],
                    'terminated_by_user_id' =>
					$request->user()->id,
                    'updated_by_user_id' =>
					$request->user()->id,
					]);
					
					$this->recordEvent(
                    agreement: $agreement,
                    eventType: 'TERMINATE',
                    userId: (int) $request->user()->id,
                    fromStatusId: $fromStatusId,
                    toStatusId: (int) $terminated->id,
                    reason: $data['termination_reason'],
                    metadata: [
					'terminated_on' =>
					$agreement->terminated_on?->format('Y-m-d'),
                    ]
					);
				});
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT',
                (int) $agreement->id,
                'TERMINATE',
                [
				'status' => [
				'from' => $currentCode,
				'to' => 'TERMINATED',
				],
				'termination_reason' =>
				$data['termination_reason'],
                ]
				);
				
				return new AgreementResource(
                $this->loadDetail($agreement->refresh())
				);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_TERMINATE_FAILED,
                'Failed to terminate agreement.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function archive(
        AgreementNotesRequest $request,
        Agreement $agreement
		) {
			if (!$this->canAccess($request, $agreement)) {
				return $this->accessDenied();
			}
			
			$currentCode = $this->agreementStatusCode($agreement);
			
			if (!in_array(
            $currentCode,
            ['EXPIRED', 'RENEWED', 'TERMINATED', 'CANCELLED'],
            true
			)) {
				return $this->invalidTransition(
                $currentCode,
                'ARCHIVED'
				);
			}
			
			try {
				$archived = $this->statusByCode('ARCHIVED');
				$fromStatusId = (int) $agreement->agreement_status_id;
				
				DB::transaction(function () use (
                $agreement,
                $archived,
                $request,
                $fromStatusId
				) {
					$agreement->update([
                    'agreement_status_id' => $archived->id,
                    'archived_at' => now(),
                    'archived_by_user_id' =>
					$request->user()->id,
                    'updated_by_user_id' =>
					$request->user()->id,
					]);
					
					$this->recordEvent(
                    agreement: $agreement,
                    eventType: 'ARCHIVE',
                    userId: (int) $request->user()->id,
                    fromStatusId: $fromStatusId,
                    toStatusId: (int) $archived->id,
                    reason: $request->validated('reason'),
                    metadata: [
					'notes' => $request->validated('notes'),
                    ]
					);
				});
				
				return new AgreementResource(
                $this->loadDetail($agreement->refresh())
				);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_ARCHIVE_FAILED,
                'Failed to archive agreement.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function cancel(
        AgreementNotesRequest $request,
        Agreement $agreement
		) {
			return $this->simpleTransition(
            request: $request,
            agreement: $agreement,
            allowedFrom: [
			'DRAFT',
			'UNDER_REVIEW',
			'PENDING_APPROVAL',
			'APPROVED',
            ],
            toCode: 'CANCELLED',
            eventType: 'CANCEL',
            errorCode: ApiErrorCode::AGREEMENT_CANCEL_FAILED,
            errorMessage: 'Failed to cancel agreement.'
			);
		}
		
		public function linkProject(
        LinkAgreementProjectRequest $request,
        Agreement $agreement
		) {
			if (!$this->canAccess($request, $agreement)) {
				return $this->accessDenied();
			}
			
			$data = $request->validated();
			
			$existing = AgreementProjectLink::query()
            ->where('agreement_id', $agreement->id)
            ->where('project_id', $data['project_id'])
            ->first();
			
			if ($existing) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_PROJECT_ALREADY_LINKED,
                'The project is already linked to this agreement.',
                [
				'link_id' => (int) $existing->id,
                ],
                409
				);
			}
			
			try {
				$link = DB::transaction(function () use (
                $agreement,
                $request,
                $data
				) {
					$link = AgreementProjectLink::create([
                    'agreement_id' => $agreement->id,
                    'project_id' => $data['project_id'],
                    'linked_by_user_id' => $request->user()->id,
                    'notes' => $data['notes'] ?? null,
					]);
					
					$this->recordEvent(
                    agreement: $agreement,
                    eventType: 'LINK_PROJECT',
                    userId: (int) $request->user()->id,
                    fromStatusId: (int) $agreement->agreement_status_id,
                    toStatusId: (int) $agreement->agreement_status_id,
                    metadata: [
					'project_id' => (int) $data['project_id'],
					'link_id' => (int) $link->id,
					'notes' => $data['notes'] ?? null,
                    ]
					);
					
					return $link;
				});
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT_PROJECT_LINK',
                (int) $link->id,
                'ATTACH',
                [
				'agreement_id' => (int) $agreement->id,
				'project_id' => (int) $data['project_id'],
                ]
				);
				
				return response()->json([
                'ok' => true,
                'link_id' => (int) $link->id,
                'agreement_id' => (int) $agreement->id,
                'project_id' => (int) $data['project_id'],
				], 201);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_PROJECT_LINK_FAILED,
                'Failed to link project to agreement.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function unlinkProject(
        Request $request,
        Agreement $agreement,
        AgreementProjectLink $link
		) {
			if (!$this->canAccess($request, $agreement)) {
				return $this->accessDenied();
			}
			
			if ((int) $link->agreement_id !== (int) $agreement->id) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_PROJECT_LINK_NOT_FOUND,
                'The project link does not belong to this agreement.',
                [],
                404
				);
			}
			
			try {
				$projectId = (int) $link->project_id;
				$linkId = (int) $link->id;
				
				DB::transaction(function () use (
                $agreement,
                $link,
                $request,
                $projectId,
                $linkId
				) {
					$link->delete();
					
					$this->recordEvent(
                    agreement: $agreement,
                    eventType: 'UNLINK_PROJECT',
                    userId: (int) $request->user()->id,
                    fromStatusId: (int) $agreement->agreement_status_id,
                    toStatusId: (int) $agreement->agreement_status_id,
                    metadata: [
					'project_id' => $projectId,
					'link_id' => $linkId,
                    ]
					);
				});
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT_PROJECT_LINK',
                $linkId,
                'DETACH',
                [
				'agreement_id' => (int) $agreement->id,
				'project_id' => $projectId,
                ]
				);
				
				return response()->json(['ok' => true]);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_PROJECT_UNLINK_FAILED,
                'Failed to unlink project from agreement.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		private function simpleTransition(
        Request $request,
        Agreement $agreement,
        array $allowedFrom,
        string $toCode,
        string $eventType,
        string $errorCode,
        string $errorMessage
		) {
			if (!$this->canAccess($request, $agreement)) {
				return $this->accessDenied();
			}
			
			$currentCode = $this->agreementStatusCode($agreement);
			
			if (!in_array($currentCode, $allowedFrom, true)) {
				return $this->invalidTransition($currentCode, $toCode);
			}
			
			try {
				$target = $this->statusByCode($toCode);
				$fromStatusId = (int) $agreement->agreement_status_id;
				
				DB::transaction(function () use (
                $agreement,
                $target,
                $request,
                $fromStatusId,
                $eventType
				) {
					$agreement->update([
                    'agreement_status_id' => $target->id,
                    'updated_by_user_id' => $request->user()->id,
					]);
					
					$this->recordEvent(
                    agreement: $agreement,
                    eventType: $eventType,
                    userId: (int) $request->user()->id,
                    fromStatusId: $fromStatusId,
                    toStatusId: (int) $target->id,
                    reason: $request->input('reason'),
                    metadata: [
					'notes' => $request->input('notes'),
                    ]
					);
				});
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT',
                (int) $agreement->id,
                $eventType,
                [
				'status' => [
				'from' => $currentCode,
				'to' => $toCode,
				],
                ]
				);
				
				return new AgreementResource(
                $this->loadDetail($agreement->refresh())
				);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                $errorCode,
                $errorMessage,
                $this->errorDetails($e),
                500
				);
			}
		}
		
		private function withSummaryRelations(Builder $query): Builder
		{
			return $query->with([
            'department:id,code,name',
            'owner:id,name,email,department_id',
            'counterparty:id,code,counterparty_type,legal_name,trading_name,registration_no',
            'category:id,code,name',
            'type:id,agreement_category_id,code,name',
            'status:id,code,name,is_terminal',
            'parentAgreement:id,agreement_no,title',
			]);
		}
		
		private function loadDetail(Agreement $agreement): Agreement
		{
			return $agreement->load([
            'department:id,code,name',
            'owner:id,name,email,department_id',
            'counterparty:id,code,counterparty_type,legal_name,trading_name,registration_no',
            'category:id,code,name',
            'type:id,agreement_category_id,code,name',
            'status:id,code,name,is_terminal',
            'parentAgreement:id,agreement_no,title',
            'childAgreements:id,agreement_no,title,lifecycle_type,parent_agreement_id,revision_no,renewal_sequence,is_current_version',
            'projects:id,code,name',
            'lifecycleEvents' => function ($query) {
                $query
				->with([
				'fromStatus:id,code,name',
				'toStatus:id,code,name',
				'relatedAgreement:id,agreement_no,title',
				'performedBy:id,name,email',
				])
				->orderByDesc('event_at')
				->orderByDesc('id');
			},
			]);
		}
		
		private function applyVisibilityScope(
        Builder $query,
        Request $request
		): void {
			$user = $request->user();
			
			if ($user->hasPermission('agreements.view.all')) {
				return;
			}
			
			$canViewDepartment = $user->hasPermission(
            'agreements.view.department'
			);
			
			$canViewOwn = $user->hasPermission(
            'agreements.view.own'
			);
			
			if (!$canViewDepartment && !$canViewOwn) {
				$query->whereRaw('1 = 0');
				return;
			}
			
			$query->where(function ($scope) use (
            $user,
            $canViewDepartment,
            $canViewOwn
			) {
				if ($canViewDepartment && $user->department_id) {
					$scope->where(
                    'department_id',
                    $user->department_id
					);
				}
				
				if ($canViewOwn) {
					if ($canViewDepartment && $user->department_id) {
						$scope->orWhere('owner_user_id', $user->id);
						} else {
						$scope->where('owner_user_id', $user->id);
					}
				}
			});
		}
		
		private function canAccess(
        Request $request,
        Agreement $agreement
		): bool {
			$user = $request->user();
			
			if ($user->hasPermission('agreements.view.all')) {
				return true;
			}
			
			if (
            $user->hasPermission('agreements.view.department')
            && $user->department_id
            && (int) $agreement->department_id
			=== (int) $user->department_id
			) {
				return true;
			}
			
			return $user->hasPermission('agreements.view.own')
            && (int) $agreement->owner_user_id
			=== (int) $user->id;
		}
		
		private function canCreateForScope(
        Request $request,
        array $data
		): bool {
			$user = $request->user();
			
			if ($user->hasPermission('agreements.view.all')) {
				return true;
			}
			
			if ($user->hasPermission('agreements.view.department')) {
				return $user->department_id
                && (int) $data['department_id']
				=== (int) $user->department_id;
			}
			
			return $user->hasPermission('agreements.view.own')
            && (int) $data['owner_user_id'] === (int) $user->id
            && $user->department_id
            && (int) $data['department_id']
			=== (int) $user->department_id;
		}
		
		private function canUpdateScope(
        Request $request,
        Agreement $agreement,
        array $data
		): bool {
			$candidateDepartment = (int) (
            $data['department_id'] ?? $agreement->department_id
			);
			
			$candidateOwner = (int) (
            $data['owner_user_id'] ?? $agreement->owner_user_id
			);
			
			return $this->canCreateForScope($request, [
            'department_id' => $candidateDepartment,
            'owner_user_id' => $candidateOwner,
			]);
		}
		
		private function validateTypeCategory(
        int $categoryId,
        ?int $typeId
		) {
			if ($typeId === null) {
				return null;
			}
			
			$valid = AgreementType::query()
            ->whereKey($typeId)
            ->where('agreement_category_id', $categoryId)
            ->where('is_active', true)
            ->exists();
			
			if ($valid) {
				return null;
			}
			
			return ApiResponse::error(
            ApiErrorCode::AGREEMENT_TYPE_CATEGORY_MISMATCH,
            'The selected agreement type does not belong to the selected agreement category.',
            [
			'agreement_category_id' => $categoryId,
			'agreement_type_id' => $typeId,
            ],
            422
			);
		}
		
		private function validateDates(
        ?string $effectiveDate,
        ?string $expiryDate
		) {
			if (!$effectiveDate || !$expiryDate) {
				return null;
			}
			
			if (Carbon::parse($expiryDate)->lt(Carbon::parse($effectiveDate))) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_INVALID_DATES,
                'The expiry date must be on or after the effective date.',
                [
				'effective_date' => $effectiveDate,
				'expiry_date' => $expiryDate,
                ],
                422
				);
			}
			
			return null;
		}
		
		private function statusByCode(string $code): AgreementStatus
		{
			$status = AgreementStatus::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();
			
			if (!$status) {
				throw new RuntimeException(
                "Agreement status {$code} is not configured or inactive."
				);
			}
			
			return $status;
		}
		
		private function agreementStatusCode(
        Agreement $agreement
		): string {
			if ($agreement->relationLoaded('status') && $agreement->status) {
				return $agreement->status->code;
			}
			
			return AgreementStatus::query()
            ->whereKey($agreement->agreement_status_id)
            ->value('code') ?? 'UNKNOWN';
		}
		
		private function recordEvent(
        Agreement $agreement,
        string $eventType,
        ?int $userId,
        ?int $fromStatusId = null,
        ?int $toStatusId = null,
        ?int $relatedAgreementId = null,
        ?string $reason = null,
        ?array $metadata = null
		): AgreementLifecycleEvent {
			return AgreementLifecycleEvent::create([
            'agreement_id' => $agreement->id,
            'event_type' => $eventType,
            'from_status_id' => $fromStatusId,
            'to_status_id' => $toStatusId,
            'related_agreement_id' => $relatedAgreementId,
            'performed_by_user_id' => $userId,
            'reason' => $reason,
            'metadata' => $metadata,
            'event_at' => now(),
			]);
		}
		
		private function copyableAgreementData(
        Agreement $agreement
		): array {
			return [
            'department_id' => $agreement->department_id,
            'owner_user_id' => $agreement->owner_user_id,
            'counterparty_id' => $agreement->counterparty_id,
            'agreement_category_id' =>
			$agreement->agreement_category_id,
            'agreement_type_id' => $agreement->agreement_type_id,
            'description' => $agreement->description,
            'purpose' => $agreement->purpose,
            'scope' => $agreement->scope,
            'effective_date' => $agreement->effective_date,
            'expiry_date' => $agreement->expiry_date,
            'signed_date' => $agreement->signed_date,
            'notice_period_days' => $agreement->notice_period_days,
            'auto_renewal' => $agreement->auto_renewal,
            'contract_value' => $agreement->contract_value,
            'currency_code' => $agreement->currency_code,
			];
		}
		
		private function copyProjectLinks(
        Agreement $source,
        Agreement $target,
        int $userId
		): void {
			$source->loadMissing('projectLinks');
			
			foreach ($source->projectLinks as $sourceLink) {
				AgreementProjectLink::create([
                'agreement_id' => $target->id,
                'project_id' => $sourceLink->project_id,
                'linked_by_user_id' => $userId,
                'notes' => $sourceLink->notes,
				]);
			}
		}
		
		private function invalidTransition(
        string $from,
        string $to
		) {
			return ApiResponse::error(
            ApiErrorCode::AGREEMENT_INVALID_TRANSITION,
            "Agreement cannot move from {$from} to {$to}.",
            [
			'from_status' => $from,
			'to_status' => $to,
            ],
            409
			);
		}
		
		private function accessDenied(
        string $message = 'You do not have access to this agreement.'
		) {
			return ApiResponse::error(
            ApiErrorCode::AGREEMENT_ACCESS_DENIED,
            $message,
            [],
            403
			);
		}
		
		private function auditSnapshot(Agreement $agreement): array
		{
			return [
            'agreement_no' => $agreement->agreement_no,
            'title' => $agreement->title,
            'department_id' => (int) $agreement->department_id,
            'owner_user_id' => (int) $agreement->owner_user_id,
            'counterparty_id' => (int) $agreement->counterparty_id,
            'agreement_category_id' =>
			(int) $agreement->agreement_category_id,
            'agreement_type_id' => $agreement->agreement_type_id,
            'agreement_status_id' =>
			(int) $agreement->agreement_status_id,
            'effective_date' =>
			$agreement->effective_date?->format('Y-m-d'),
            'expiry_date' =>
			$agreement->expiry_date?->format('Y-m-d'),
            'lifecycle_type' => $agreement->lifecycle_type,
            'revision_no' => (int) $agreement->revision_no,
            'renewal_sequence' =>
			(int) $agreement->renewal_sequence,
            'is_current_version' =>
			(int) $agreement->is_current_version,
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