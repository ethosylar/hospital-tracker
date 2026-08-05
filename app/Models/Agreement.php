<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\SoftDeletes;
	use Illuminate\Support\Str;
	
	class Agreement extends Model
	{
		use SoftDeletes;
		
		protected $table = 'dt_agreements';
		
		protected $fillable = [
        'agreement_no',
        'title',
        'department_id',
        'owner_user_id',
        'counterparty_id',
        'agreement_category_id',
        'agreement_type_id',
        'agreement_status_id',
        'description',
        'purpose',
        'scope',
        'effective_date',
        'expiry_date',
        'signed_date',
        'notice_period_days',
        'auto_renewal',
        'contract_value',
        'currency_code',
        'lifecycle_type',
        'parent_agreement_id',
        'root_agreement_id',
        'revision_no',
        'renewal_sequence',
        'is_current_version',
        'submitted_at',
        'submitted_by_user_id',
        'approved_at',
        'approved_by_user_id',
        'terminated_on',
        'termination_reason',
        'terminated_by_user_id',
        'archived_at',
        'archived_by_user_id',
        'created_by_user_id',
        'updated_by_user_id',
		];
		
		protected $casts = [
        'department_id' => 'integer',
        'owner_user_id' => 'integer',
        'counterparty_id' => 'integer',
        'agreement_category_id' => 'integer',
        'agreement_type_id' => 'integer',
        'agreement_status_id' => 'integer',
        'parent_agreement_id' => 'integer',
        'root_agreement_id' => 'integer',
        'revision_no' => 'integer',
        'renewal_sequence' => 'integer',
        'notice_period_days' => 'integer',
        'auto_renewal' => 'boolean',
        'contract_value' => 'decimal:2',
        'is_current_version' => 'boolean',
        'effective_date' => 'date',
        'expiry_date' => 'date',
        'signed_date' => 'date',
        'terminated_on' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'archived_at' => 'datetime',
		];
		
		protected static function booted(): void
		{
			static::creating(function (Agreement $agreement) {
				if (blank($agreement->agreement_no)) {
					$agreement->agreement_no = static::generateAgreementNo();
				}
				
				$agreement->currency_code = strtoupper(
                trim($agreement->currency_code ?: 'MYR')
				);
			});
		}
		
		public static function generateAgreementNo(): string
		{
			do {
				$number = 'AGR-'
                . now()->format('Ymd')
                . '-'
                . Str::upper(Str::random(6));
			} while (
            static::withTrashed()
			->where('agreement_no', $number)
			->exists()
			);
			
			return $number;
		}
		
		public function department()
		{
			return $this->belongsTo(Department::class, 'department_id');
		}
		
		public function owner()
		{
			return $this->belongsTo(User::class, 'owner_user_id');
		}
		
		public function counterparty()
		{
			return $this->belongsTo(Counterparty::class, 'counterparty_id');
		}
		
		public function category()
		{
			return $this->belongsTo(
            AgreementCategory::class,
            'agreement_category_id'
			);
		}
		
		public function type()
		{
			return $this->belongsTo(
            AgreementType::class,
            'agreement_type_id'
			);
		}
		
		public function status()
		{
			return $this->belongsTo(
            AgreementStatus::class,
            'agreement_status_id'
			);
		}
		
		public function parentAgreement()
		{
			return $this->belongsTo(
            Agreement::class,
            'parent_agreement_id'
			);
		}
		
		public function rootAgreement()
		{
			return $this->belongsTo(
            Agreement::class,
            'root_agreement_id'
			);
		}
		
		public function childAgreements()
		{
			return $this->hasMany(
            Agreement::class,
            'parent_agreement_id'
			);
		}
		
		public function projectLinks()
		{
			return $this->hasMany(
            AgreementProjectLink::class,
            'agreement_id'
			);
		}
		
		public function projects()
		{
			return $this->belongsToMany(
            Project::class,
            'dt_agreement_project_links',
            'agreement_id',
            'project_id'
			)->withPivot([
            'id',
            'linked_by_user_id',
            'notes',
            'created_at',
            'updated_at',
			]);
		}
		
		public function lifecycleEvents()
		{
			return $this->hasMany(
            AgreementLifecycleEvent::class,
            'agreement_id'
			);
		}
		
		public function documents()
		{
			return $this->hasMany(
			AgreementFile::class,
			'agreement_id'
			);
		}
		
		public function createdBy()
		{
			return $this->belongsTo(User::class, 'created_by_user_id');
		}
		
		public function updatedBy()
		{
			return $this->belongsTo(User::class, 'updated_by_user_id');
		}
		
		public function submittedBy()
		{
			return $this->belongsTo(User::class, 'submitted_by_user_id');
		}
		
		public function approvedBy()
		{
			return $this->belongsTo(User::class, 'approved_by_user_id');
		}
		
		public function terminatedBy()
		{
			return $this->belongsTo(User::class, 'terminated_by_user_id');
		}
		
		public function archivedBy()
		{
			return $this->belongsTo(User::class, 'archived_by_user_id');
		}
	}		