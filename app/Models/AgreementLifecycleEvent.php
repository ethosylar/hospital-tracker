<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	
	class AgreementLifecycleEvent extends Model
	{
		protected $table = 'dt_agreement_lifecycle_events';
		
		protected $fillable = [
        'agreement_id',
        'event_type',
        'from_status_id',
        'to_status_id',
        'related_agreement_id',
        'performed_by_user_id',
        'reason',
        'metadata',
        'event_at',
		];
		
		protected $casts = [
        'agreement_id' => 'integer',
        'from_status_id' => 'integer',
        'to_status_id' => 'integer',
        'related_agreement_id' => 'integer',
        'performed_by_user_id' => 'integer',
        'metadata' => 'array',
        'event_at' => 'datetime',
		];
		
		public function agreement()
		{
			return $this->belongsTo(Agreement::class, 'agreement_id');
		}
		
		public function fromStatus()
		{
			return $this->belongsTo(
            AgreementStatus::class,
            'from_status_id'
			);
		}
		
		public function toStatus()
		{
			return $this->belongsTo(
            AgreementStatus::class,
            'to_status_id'
			);
		}
		
		public function relatedAgreement()
		{
			return $this->belongsTo(
            Agreement::class,
            'related_agreement_id'
			);
		}
		
		public function performedBy()
		{
			return $this->belongsTo(
            User::class,
            'performed_by_user_id'
			);
		}
	}	