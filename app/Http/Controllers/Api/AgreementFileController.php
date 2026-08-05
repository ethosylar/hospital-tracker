<?php
	
	namespace App\Http\Controllers\Api;
	
	use App\Http\Controllers\Controller;
	use App\Http\Requests\AgreementFileIndexRequest;
	use App\Http\Requests\StoreAgreementFileRequest;
	use App\Http\Requests\UpdateAgreementFileRequest;
	use App\Http\Resources\AgreementFileResource;
	use App\Models\Agreement;
	use App\Models\AgreementFile;
	use App\Models\FileTextExtraction;
	use App\Models\StoredFile;
	use App\Support\ApiErrorCode;
	use App\Support\ApiResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Storage;
	use Throwable;
	
	class AgreementFileController extends Controller
	{
		public function index(
        AgreementFileIndexRequest $request,
        Agreement $agreement
		) {
			if (!$this->canAccess($request, $agreement)) {
				return $this->accessDenied();
			}
			
			$data = $request->validated();
			
			$query = AgreementFile::query()
            ->where('agreement_id', $agreement->id)
            ->with($this->relations());
			
			if (!empty($data['document_type_id'])) {
				$query->where(
                'document_type_id',
                (int) $data['document_type_id']
				);
			}
			
			if (array_key_exists('is_current', $data)) {
				$query->where('is_current', (bool) $data['is_current']);
			}
			
			if (array_key_exists('is_executed_copy', $data)) {
				$query->where(
                'is_executed_copy',
                (bool) $data['is_executed_copy']
				);
			}
			
			if (!empty($data['ocr_status'])) {
				$query->whereHas(
                'file.textExtraction',
                fn ($ocrQuery) => $ocrQuery->where(
				'status',
				$data['ocr_status']
                )
				);
			}
			
			$perPage = max(
            1,
            min((int) ($data['per_page'] ?? 50), 100)
			);
			
			return AgreementFileResource::collection(
            $query
			->orderByDesc('is_current')
			->orderByDesc('document_date')
			->orderByDesc('id')
			->paginate($perPage)
			);
		}
		
		public function show(
        Request $request,
        Agreement $agreement,
        AgreementFile $agreementFile
		) {
			if (!$this->canAccess($request, $agreement)) {
				return $this->accessDenied();
			}
			
			if (!$this->belongsToAgreement($agreement, $agreementFile)) {
				return $this->notLinked();
			}
			
			return new AgreementFileResource(
            $agreementFile->load($this->relations())
			);
		}
		
		public function store(
        StoreAgreementFileRequest $request,
        Agreement $agreement
		) {
			if (!$this->canAccess($request, $agreement)) {
				return $this->accessDenied();
			}
			
			$data = $request->validated();
			$uploaded = $request->file('file');
			
			if (!$uploaded) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_FILE_UPLOAD_FAILED,
                'Missing agreement document file.',
                [],
                422
				);
			}
			
			$supersedesError = $this->validateSupersedes(
            $agreement,
            isset($data['supersedes_agreement_file_id'])
			? (int) $data['supersedes_agreement_file_id']
			: null
			);
			
			if ($supersedesError) {
				return $supersedesError;
			}
			
			$disk = config('filesystems.default', 'local');
			$directory = sprintf(
            'uploads/agreements/%d/%s',
            $agreement->id,
            now()->format('Y/m')
			);
			
			$path = null;
			
			try {
				$path = $uploaded->store($directory, $disk);
				$checksum = hash_file('sha256', $uploaded->getRealPath());
				
				$agreementFile = DB::transaction(function () use (
                $request,
                $agreement,
                $data,
                $uploaded,
                $disk,
                $path,
                $checksum
				) {
					$storedFile = StoredFile::create([
                    'disk' => $disk,
                    'path' => $path,
                    'original_name' => $uploaded->getClientOriginalName(),
                    'mime_type' => $uploaded->getClientMimeType(),
                    'size' => $uploaded->getSize() ?? 0,
                    'checksum' => $checksum,
                    'uploaded_by_user_id' => $request->user()->id,
					]);
					
					$isCurrent = array_key_exists('is_current', $data)
                    ? (bool) $data['is_current']
                    : true;
					
					if ($isCurrent) {
						AgreementFile::query()
                        ->where('agreement_id', $agreement->id)
                        ->where(
						'document_type_id',
						(int) $data['document_type_id']
                        )
                        ->where('is_current', true)
                        ->update(['is_current' => false]);
					}
					
					$link = AgreementFile::create([
                    'agreement_id' => $agreement->id,
                    'file_id' => $storedFile->id,
                    'document_type_id' =>
					(int) $data['document_type_id'],
                    'document_version' =>
					$data['document_version'] ?? null,
                    'document_date' => $data['document_date'] ?? null,
                    'is_current' => $isCurrent,
                    'is_executed_copy' =>
					(bool) ($data['is_executed_copy'] ?? false),
                    'supersedes_agreement_file_id' =>
					$data['supersedes_agreement_file_id'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'linked_by_user_id' => $request->user()->id,
					]);
					
					FileTextExtraction::create([
                    'file_id' => $storedFile->id,
                    'status' => FileTextExtraction::STATUS_NOT_REQUESTED,
                    'engine' => null,
                    'language' => config(
					'agreement_documents.ocr.language',
					'eng'
                    ),
                    'source_checksum' => $checksum,
					]);
					
					return $link;
				});
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT_FILE',
                (int) $agreementFile->id,
                'UPLOAD',
                [
				'agreement_id' => (int) $agreement->id,
				'agreement_no' => $agreement->agreement_no,
				'file_id' => (int) $agreementFile->file_id,
				'document_type_id' =>
				(int) $agreementFile->document_type_id,
				'document_version' =>
				$agreementFile->document_version,
				'is_current' => (int) $agreementFile->is_current,
				'is_executed_copy' =>
				(int) $agreementFile->is_executed_copy,
				'supersedes_agreement_file_id' =>
				$agreementFile->supersedes_agreement_file_id,
				'original_name' =>
				$uploaded->getClientOriginalName(),
				'mime_type' => $uploaded->getClientMimeType(),
				'size' => $uploaded->getSize() ?? 0,
				'checksum' => $checksum,
				'disk' => $disk,
				'path' => $path,
				'ocr_status' =>
				FileTextExtraction::STATUS_NOT_REQUESTED,
                ]
				);
				
				return (new AgreementFileResource(
                $agreementFile->load($this->relations())
				))->response()->setStatusCode(201);
				} catch (Throwable $e) {
				report($e);
				
				// The DB transaction may fail after the physical file is stored.
				if ($path && Storage::disk($disk)->exists($path)) {
					Storage::disk($disk)->delete($path);
				}
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_FILE_UPLOAD_FAILED,
                'Failed to upload agreement document.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function update(
        UpdateAgreementFileRequest $request,
        Agreement $agreement,
        AgreementFile $agreementFile
		) {
			if (!$this->canAccess($request, $agreement)) {
				return $this->accessDenied();
			}
			
			if (!$this->belongsToAgreement($agreement, $agreementFile)) {
				return $this->notLinked();
			}
			
			$data = $request->validated();
			
			if (empty($data)) {
				return new AgreementFileResource(
                $agreementFile->load($this->relations())
				);
			}
			
			$supersedesId = array_key_exists(
            'supersedes_agreement_file_id',
            $data
			)
            ? ($data['supersedes_agreement_file_id'] !== null
			? (int) $data['supersedes_agreement_file_id']
			: null)
            : $agreementFile->supersedes_agreement_file_id;
			
			if ($supersedesId === (int) $agreementFile->id) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_FILE_INVALID_SUPERSEDES,
                'An agreement document cannot supersede itself.',
                [],
                422
				);
			}
			
			$supersedesError = $this->validateSupersedes(
            $agreement,
            $supersedesId
			);
			
			if ($supersedesError) {
				return $supersedesError;
			}
			
			$candidateTypeId = (int) (
            $data['document_type_id']
            ?? $agreementFile->document_type_id
			);
			
			$isCurrent = array_key_exists('is_current', $data)
            ? (bool) $data['is_current']
            : (bool) $agreementFile->is_current;
			
			$changes = \App\Support\AuditDiff::diff(
            $agreementFile->getOriginal(),
            $data
			);
			
			if (empty($changes)) {
				return new AgreementFileResource(
                $agreementFile->load($this->relations())
				);
			}
			
			try {
				DB::transaction(function () use (
                $agreement,
                $agreementFile,
                $data,
                $candidateTypeId,
                $isCurrent
				) {
					if ($isCurrent) {
						AgreementFile::query()
                        ->where('agreement_id', $agreement->id)
                        ->where('document_type_id', $candidateTypeId)
                        ->where('id', '!=', $agreementFile->id)
                        ->where('is_current', true)
                        ->update(['is_current' => false]);
					}
					
					$agreementFile->update($data);
				});
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT_FILE',
                (int) $agreementFile->id,
                'UPDATE',
                $changes
				);
				
				return new AgreementFileResource(
                $agreementFile->refresh()->load($this->relations())
				);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_FILE_UPDATE_FAILED,
                'Failed to update agreement document metadata.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		public function download(
        Request $request,
        Agreement $agreement,
        AgreementFile $agreementFile
		) {
			if (!$this->canAccess($request, $agreement)) {
				return $this->accessDenied();
			}
			
			if (!$this->belongsToAgreement($agreement, $agreementFile)) {
				return $this->notLinked();
			}
			
			$agreementFile->load('file');
			$file = $agreementFile->file;
			
			if (!$file || !Storage::disk($file->disk)->exists($file->path)) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_FILE_PHYSICAL_MISSING,
                'Physical agreement document was not found.',
                [],
                404
				);
			}
			
			\App\Support\Audit::log(
            $request->user()->id,
            'AGREEMENT_FILE',
            (int) $agreementFile->id,
            'DOWNLOAD',
            [
			'agreement_id' => (int) $agreement->id,
			'agreement_no' => $agreement->agreement_no,
			'file_id' => (int) $file->id,
			'original_name' => $file->original_name,
			'mime_type' => $file->mime_type,
			'size' => (int) $file->size,
			'ip' => $request->ip(),
			'user_agent' => $request->userAgent(),
            ]
			);
			
			return Storage::disk($file->disk)->download(
            $file->path,
            $file->original_name
			);
		}
		
		public function destroy(
        Request $request,
        Agreement $agreement,
        AgreementFile $agreementFile
		) {
			if (!$this->canAccess($request, $agreement)) {
				return $this->accessDenied();
			}
			
			if (!$this->belongsToAgreement($agreement, $agreementFile)) {
				return $this->notLinked();
			}
			
			$agreementFile->load('file', 'documentType');
			$file = $agreementFile->file;
			
			$snapshot = [
            'agreement_id' => (int) $agreement->id,
            'agreement_no' => $agreement->agreement_no,
            'agreement_file_id' => (int) $agreementFile->id,
            'file_id' => (int) $agreementFile->file_id,
            'document_type_id' =>
			(int) $agreementFile->document_type_id,
            'document_type_code' =>
			$agreementFile->documentType?->code,
            'document_version' => $agreementFile->document_version,
            'original_name' => $file?->original_name,
			];
			
			try {
				$result = DB::transaction(function () use (
                $agreementFile,
                $file
				) {
					$agreementFile->delete();
					
					if (!$file) {
						return ['physical_deleted' => false];
					}
					
					$stillReferenced =
                    DB::table('dt_project_files')
					->where('file_id', $file->id)
					->exists()
                    || DB::table('dt_task_files')
					->where('file_id', $file->id)
					->exists()
                    || DB::table('dt_agreement_files')
					->where('file_id', $file->id)
					->exists();
					
					if ($stillReferenced) {
						return ['physical_deleted' => false];
					}
					
					Storage::disk($file->disk)->delete($file->path);
					$file->delete();
					
					return ['physical_deleted' => true];
				});
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT_FILE',
                (int) $agreementFile->id,
                'DETACH',
                [
				...$snapshot,
				'physical_deleted' =>
				(int) $result['physical_deleted'],
                ]
				);
				
				return response()->json([
                'ok' => true,
                'physical_deleted' =>
				(bool) $result['physical_deleted'],
				]);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_FILE_DETACH_FAILED,
                'Failed to remove agreement document.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		/**
			* This endpoint prepares a file for a future OCR processor.
			* It does not execute OCR by itself.
		*/
		public function requestOcr(
        Request $request,
        Agreement $agreement,
        AgreementFile $agreementFile
		) {
			if (!$this->canAccess($request, $agreement)) {
				return $this->accessDenied();
			}
			
			if (!$this->belongsToAgreement($agreement, $agreementFile)) {
				return $this->notLinked();
			}
			
			if (!config('agreement_documents.ocr.enabled', false)) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_OCR_DISABLED,
                'OCR is currently disabled for agreement documents.',
                [
				'activation_setting' =>
				'AGREEMENT_OCR_ENABLED=true',
                ],
                503
				);
			}
			
			$agreementFile->load('documentType', 'file.textExtraction');
			
			if (!$agreementFile->documentType?->ocr_eligible) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_OCR_NOT_ELIGIBLE,
                'This agreement document type is not eligible for OCR.',
                [
				'document_type_id' =>
				(int) $agreementFile->document_type_id,
				'document_type_code' =>
				$agreementFile->documentType?->code,
                ],
                422
				);
			}
			
			$file = $agreementFile->file;
			
			if (!$file || !Storage::disk($file->disk)->exists($file->path)) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_FILE_PHYSICAL_MISSING,
                'Physical agreement document was not found.',
                [],
                404
				);
			}
			
			$existing = $file->textExtraction;
			
			if ($existing?->status === FileTextExtraction::STATUS_PROCESSING) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_OCR_ALREADY_PROCESSING,
                'OCR is already processing this document.',
                [],
                409
				);
			}
			
			try {
				FileTextExtraction::updateOrCreate(
                ['file_id' => $file->id],
                [
				'status' => FileTextExtraction::STATUS_PENDING,
				'engine' => config(
				'agreement_documents.ocr.engine'
				),
				'language' => config(
				'agreement_documents.ocr.language',
				'eng'
				),
				'source_checksum' => $file->checksum,
				'extracted_text' => null,
				'page_count' => null,
				'error_message' => null,
				'requested_by_user_id' => $request->user()->id,
				'requested_at' => now(),
				'started_at' => null,
				'completed_at' => null,
                ]
				);
				
				\App\Support\Audit::log(
                $request->user()->id,
                'AGREEMENT_FILE',
                (int) $agreementFile->id,
                'OCR_REQUESTED',
                [
				'agreement_id' => (int) $agreement->id,
				'file_id' => (int) $file->id,
				'source_checksum' => $file->checksum,
				'engine' => config(
				'agreement_documents.ocr.engine'
				),
				'language' => config(
				'agreement_documents.ocr.language',
				'eng'
				),
                ]
				);
				
				return (new AgreementFileResource(
                $agreementFile->refresh()->load($this->relations())
				))->response()->setStatusCode(202);
				} catch (Throwable $e) {
				report($e);
				
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_OCR_REQUEST_FAILED,
                'Failed to queue agreement document for OCR.',
                $this->errorDetails($e),
                500
				);
			}
		}
		
		private function validateSupersedes(
        Agreement $agreement,
        ?int $supersedesId
		) {
			if ($supersedesId === null) {
				return null;
			}
			
			$previous = AgreementFile::query()
            ->with('agreement:id,root_agreement_id')
            ->find($supersedesId);
			
			if (!$previous || !$previous->agreement) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_FILE_INVALID_SUPERSEDES,
                'The document selected as superseded was not found.',
                [],
                422
				);
			}
			
			$currentRootId = (int) (
            $agreement->root_agreement_id ?: $agreement->id
			);
			
			$previousRootId = (int) (
            $previous->agreement->root_agreement_id
			?: $previous->agreement->id
			);
			
			if ($currentRootId !== $previousRootId) {
				return ApiResponse::error(
                ApiErrorCode::AGREEMENT_FILE_INVALID_SUPERSEDES,
                'The superseded document must belong to the same agreement lifecycle.',
                [
				'current_root_agreement_id' => $currentRootId,
				'previous_root_agreement_id' => $previousRootId,
                ],
                422
				);
			}
			
			return null;
		}
		
		private function relations(): array
		{
			return [
            'documentType:id,code,name,ocr_eligible',
            'file.textExtraction',
            'linkedBy:id,name,email',
			];
		}
		
		private function belongsToAgreement(
        Agreement $agreement,
        AgreementFile $agreementFile
		): bool {
			return (int) $agreementFile->agreement_id
            === (int) $agreement->id;
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
		
		private function accessDenied()
		{
			return ApiResponse::error(
            ApiErrorCode::AGREEMENT_ACCESS_DENIED,
            'You do not have access to this agreement.',
            [],
            403
			);
		}
		
		private function notLinked()
		{
			return ApiResponse::error(
            ApiErrorCode::AGREEMENT_FILE_NOT_LINKED,
            'The document is not linked to this agreement.',
            [],
            404
			);
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