<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\CounterpartyIndexRequest;
	use App\Http\Requests\StoreCounterpartyRequest;
	use App\Http\Requests\UpdateCounterpartyRequest;
	use App\Http\Resources\CounterpartyResource;
	use App\Models\Counterparty;
	use App\Support\ApiErrorCode;
	use App\Support\ApiResponse;
	use Illuminate\Http\Request;
	use Throwable;
	
	class CounterpartyController extends Controller
	{
		public function index(CounterpartyIndexRequest $request)
		{
			$data = $request->validated();
			$query = Counterparty::query();
			
			if (!empty($data['counterparty_type'])) {
				$query->where(
                'counterparty_type',
                $data['counterparty_type']
				);
			}
			
			if (array_key_exists('is_active', $data)) {
				$query->where(
                'is_active',
                (bool) $data['is_active']
				);
			}
			
			if (!empty($data['search'])) {
				$search = trim((string) $data['search']);
				
				$query->where(function ($where) use ($search) {
					$where
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%")
                    ->orWhere('trading_name', 'like', "%{$search}%")
                    ->orWhere('registration_no', 'like', "%{$search}%")
                    ->orWhere('vendor_no', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
				});
			}
			
			$perPage = max(
            1,
            min((int) ($data['per_page'] ?? 50), 100)
			);
			
			return CounterpartyResource::collection(
            $query
			->orderByDesc('is_active')
			->orderBy('legal_name')
			->paginate($perPage)
			);
		}
		
		public function show(Counterparty $counterparty)
		{
			return new CounterpartyResource($counterparty);
		}
		
		public function store(StoreCounterpartyRequest $request)
		{
			$data = $request->validated();
			
			$duplicate = $this->findDuplicate(
            legalName: $data['legal_name'],
            registrationNo: $data['registration_no'] ?? null
			);
			
			if ($duplicate) {
				return ApiResponse::error(
                ApiErrorCode::COUNTERPARTY_DUPLICATE,
                'A counterparty with the same legal name or registration number already exists.',
                [
				'existing_counterparty' => [
				'id' => (int) $duplicate->id,
				'code' => $duplicate->code,
				'legal_name' => $duplicate->legal_name,
				'registration_no' => $duplicate->registration_no,
				'is_active' => (bool) $duplicate->is_active,
				],
                ],
                409
				);
			}
			
			$data['country'] = $data['country'] ?? 'Malaysia';
			$data['is_active'] = array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : true;
			
			try {
				$counterparty = Counterparty::create($data);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'COUNTERPARTY',
                (int) $counterparty->id,
                'CREATE',
                $this->auditSnapshot($counterparty)
				);
				
				return (new CounterpartyResource($counterparty))
                ->response()
                ->setStatusCode(201);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::COUNTERPARTY_CREATE_FAILED,
                'Failed to create counterparty.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function update(
        UpdateCounterpartyRequest $request,
        Counterparty $counterparty
		) {
			$data = $request->validated();
			
			if (empty($data)) {
				return new CounterpartyResource($counterparty);
			}
			
			$candidateName = $data['legal_name']
            ?? $counterparty->legal_name;
			
			$candidateRegistrationNo = array_key_exists(
            'registration_no',
            $data
			)
            ? $data['registration_no']
            : $counterparty->registration_no;
			
			$duplicate = $this->findDuplicate(
            legalName: $candidateName,
            registrationNo: $candidateRegistrationNo,
            excludeId: (int) $counterparty->id
			);
			
			if ($duplicate) {
				return ApiResponse::error(
                ApiErrorCode::COUNTERPARTY_DUPLICATE,
                'A counterparty with the same legal name or registration number already exists.',
                [
				'existing_counterparty' => [
				'id' => (int) $duplicate->id,
				'code' => $duplicate->code,
				'legal_name' => $duplicate->legal_name,
				'registration_no' => $duplicate->registration_no,
				'is_active' => (bool) $duplicate->is_active,
				],
                ],
                409
				);
			}
			
			$changes = \App\Support\AuditDiff::diff(
            $counterparty->getOriginal(),
            $data
			);
			
			if (empty($changes)) {
				return new CounterpartyResource($counterparty);
			}
			
			try {
				$counterparty->update($data);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'COUNTERPARTY',
                (int) $counterparty->id,
                'UPDATE',
                $changes
				);
				
				return new CounterpartyResource(
                $counterparty->refresh()
				);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::COUNTERPARTY_UPDATE_FAILED,
                'Failed to update counterparty.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function destroy(
        Request $request,
        Counterparty $counterparty
		) {
			if (!$counterparty->is_active) {
				return response()->json([
                'ok' => true,
                'mode' => 'SOFT',
                'message' => 'Counterparty is already inactive.',
				]);
			}
			
			try {
				$counterparty->update([
                'is_active' => false,
				]);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'COUNTERPARTY',
                (int) $counterparty->id,
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
                ApiErrorCode::COUNTERPARTY_DELETE_FAILED,
                'Failed to deactivate counterparty.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		private function findDuplicate(
        string $legalName,
        ?string $registrationNo = null,
        ?int $excludeId = null
		): ?Counterparty {
			$normalizedName = Counterparty::normalizeName($legalName);
			$normalizedRegistrationNo =
            Counterparty::normalizeRegistrationNo($registrationNo);
			
			return Counterparty::query()
            ->when(
			$excludeId !== null,
			fn ($query) => $query->where('id', '!=', $excludeId)
            )
            ->where(function ($where) use (
			$normalizedName,
			$normalizedRegistrationNo
            ) {
                $where->where('normalized_name', $normalizedName);
				
                if ($normalizedRegistrationNo !== null) {
                    $where->orWhere(
					'registration_no',
					$normalizedRegistrationNo
                    );
				}
			})
            ->first();
		}
		
		private function auditSnapshot(Counterparty $counterparty): array
		{
			return [
            'code' => $counterparty->code,
            'counterparty_type' => $counterparty->counterparty_type,
            'legal_name' => $counterparty->legal_name,
            'trading_name' => $counterparty->trading_name,
            'registration_no' => $counterparty->registration_no,
            'tax_no' => $counterparty->tax_no,
            'vendor_no' => $counterparty->vendor_no,
            'contact_person' => $counterparty->contact_person,
            'contact_position' => $counterparty->contact_position,
            'email' => $counterparty->email,
            'phone' => $counterparty->phone,
            'city' => $counterparty->city,
            'state' => $counterparty->state,
            'country' => $counterparty->country,
            'is_active' => (int) $counterparty->is_active,
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