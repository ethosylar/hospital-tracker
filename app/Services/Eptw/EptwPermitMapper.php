<?php
	
	namespace App\Services\Eptw;
	
	use App\Models\ExternalSource;
	use InvalidArgumentException;
	
	class EptwPermitMapper
	{
		public function __construct(
        private readonly EptwStatusMapper $statusMapper
		) {
		}
		
		public function map(array $record, ExternalSource $source): array
		{
			$externalFormId = $this->value($record, [
            'external_form_id',
            'form_id',
            'id',
			]);
			
			if ($externalFormId === null) {
				throw new InvalidArgumentException(
                'The ePTW record does not contain an external form ID.'
				);
			}
			
			$rawStatus = $this->value($record, [
            'status',
            'raw_status',
			]);
			
			$sourceUrl = $this->value($record, [
            'source_url',
            'detail_url',
			]);
			
			if ($sourceUrl === null && !empty($source->base_url)) {
				$sourceUrl = rtrim($source->base_url, '/')
                . '/edit.php?id='
                . urlencode($externalFormId);
			}
			
			return [
            'external_source_id' => (int) $source->id,
			
            'external_form_id' => $externalFormId,
			
            'external_permit_id' => $this->value($record, [
			'external_permit_id',
			'permit_id',
			'permitID',
            ]),
			
            'raw_status' => $rawStatus,
            'normalized_status' => $this->statusMapper->map($rawStatus),
			
            'applicant_name' => $this->value($record, [
			'applicant_name',
			'name',
            ]),
			
            'service_name' => $this->value($record, [
			'service_name',
			'services',
            ]),
			
            'company_name' => $this->value($record, [
			'company_name',
			'companyName',
            ]),
			
            'supervisor_name' => $this->value($record, [
			'supervisor_name',
			'svName',
            ]),
			
            'exact_location' => $this->value($record, [
			'exact_location',
			'exactLocation',
            ]),
			
            'work_type' => $this->value($record, [
			'work_type',
			'workType',
            ]),
			
            'hazards' => $this->value($record, ['hazards']),
            'ppe' => $this->value($record, ['ppe']),
			
            'worksite_controls' => $this->value($record, [
			'worksite_controls',
			'worksite',
            ]),
			
            'infection_controls' => $this->value($record, [
			'infection_controls',
			'infection',
            ]),
			
            'remark' => $this->value($record, ['remark']),
			
            'work_start_date' => $this->value($record, [
			'work_start_date',
			'durationFrom',
            ]),
			
            'work_end_date' => $this->value($record, [
			'work_end_date',
			'durationTo',
            ]),
			
            'work_start_time' => $this->normalizeTime(
			$this->value($record, [
			'work_start_time',
			'timeFrom',
			])
            ),
			
            'work_end_time' => $this->normalizeTime(
			$this->value($record, [
			'work_end_time',
			'timeTo',
			])
            ),
			
            'brief_date' => $this->value($record, [
			'brief_date',
			'briefDate',
            ]),
			
            'brief_time' => $this->normalizeTime(
			$this->value($record, [
			'brief_time',
			'briefTime',
			])
            ),
			
            'brief_conducted_by' => $this->value($record, [
			'brief_conducted_by',
			'briefConducted',
            ]),
			
            'source_created_at' => $this->value($record, [
			'source_created_at',
			'date',
            ]),
			
            'source_updated_at' => $this->value($record, [
			'source_updated_at',
			'updated_at',
            ]),
			
            'source_url' => $sourceUrl,
			
            'is_source_deleted' => (bool) (
			$record['is_deleted']
			?? $record['is_source_deleted']
			?? false
            ),
			];
		}
		
		private function value(array $record, array $keys): ?string
		{
			foreach ($keys as $key) {
				if (!array_key_exists($key, $record)) {
					continue;
				}
				
				if ($record[$key] === null) {
					return null;
				}
				
				$value = trim((string) $record[$key]);
				
				return $value === '' ? null : $value;
			}
			
			return null;
		}
		
		private function normalizeTime(?string $time): ?string
		{
			if ($time === null || trim($time) === '') {
				return null;
			}
			
			$time = trim($time);
			
			// Convert HH:mm to HH:mm:ss.
			if (preg_match('/^\d{2}:\d{2}$/', $time)) {
				return $time . ':00';
			}
			
			return $time;
		}
	}	