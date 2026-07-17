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
			
            'work_start_date' => $this->cleanDate(
			$record['work_start_date']
			?? $record['durationFrom']
			?? null
			),
			
			'work_end_date' => $this->cleanDate(
			$record['work_end_date']
			?? $record['durationTo']
			?? null
			),
			
			'work_start_time' => $this->cleanTime(
			$record['work_start_time']
			?? $record['timeFrom']
			?? null
			),
			
			'work_end_time' => $this->cleanTime(
			$record['work_end_time']
			?? $record['timeTo']
			?? null
			),
			
			'brief_date' => $this->cleanDate(
			$record['brief_date']
			?? $record['briefDate']
			?? $record['BriefDate']
			?? null
			),
			
			'brief_time' => $this->cleanTime(
			$record['brief_time']
			?? $record['briefTime']
			?? $record['BriefTime']
			?? null,
			true
			),
			
            'brief_conducted_by' => $this->value($record, [
			'brief_conducted_by',
			'briefConducted',
            ]),
			
            'source_created_at' => $this->cleanDateTime(
			$record['source_created_at']
			?? $record['created_at']
			?? $record['date']
			?? null
			),
			
			'source_updated_at' => $this->cleanDateTime(
			$record['source_updated_at']
			?? $record['updated_at']
			?? $record['date']
			?? null
			),
			
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
		
		private function cleanDate(mixed $value): ?string
		{
			if ($value === null) {
				return null;
			}
			
			$value = trim((string) $value);
			
			if (
			$value === '' ||
			$value === '0000-00-00' ||
			$value === '0000/00/00'
			) {
				return null;
			}
			
			try {
				return \Carbon\Carbon::parse($value)->format('Y-m-d');
				} catch (\Throwable) {
				return null;
			}
		}
		
		private function cleanTime(mixed $value, bool $zeroAsNull = false): ?string
		{
			if ($value === null) {
				return null;
			}
			
			$value = trim((string) $value);
			
			if ($value === '') {
				return null;
			}
			
			if ($zeroAsNull && in_array($value, ['00:00', '00:00:00'], true)) {
				return null;
			}
			
			try {
				return \Carbon\Carbon::parse($value)->format('H:i:s');
				} catch (\Throwable) {
				return null;
			}
		}
		
		private function cleanDateTime(mixed $value): ?string
		{
			if ($value === null) {
				return null;
			}
			
			$value = trim((string) $value);
			
			if (
			$value === '' ||
			$value === '0000-00-00' ||
			$value === '0000-00-00 00:00:00' ||
			$value === '0000/00/00' ||
			$value === '0000/00/00 00:00:00'
			) {
				return null;
			}
			
			try {
				return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
				} catch (\Throwable) {
				return null;
			}
		}
	}				