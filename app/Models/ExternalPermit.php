<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\BelongsToMany;
	use Illuminate\Database\Eloquent\Relations\HasMany;
	
	class ExternalPermit extends Model
	{
		protected $table = 'dt_external_permits';
		
		protected $fillable = [
        'external_source_id',
        'external_form_id',
        'external_permit_id',
		
        'raw_status',
        'normalized_status',
		
        'applicant_name',
        'service_name',
        'company_name',
        'supervisor_name',
        'exact_location',
		
        'work_type',
        'hazards',
        'ppe',
        'worksite_controls',
        'infection_controls',
        'remark',
		
        'work_start_date',
        'work_end_date',
        'work_start_time',
        'work_end_time',
		
        'brief_date',
        'brief_time',
        'brief_conducted_by',
		
        'source_created_at',
        'source_updated_at',
        'last_synced_at',
        'last_seen_at',
		
        'source_url',
        'source_hash',
		
        'is_source_deleted',
        'source_deleted_at',
		];
		
		protected $casts = [
        'external_source_id' => 'integer',
		
        'work_start_date' => 'date:Y-m-d',
        'work_end_date' => 'date:Y-m-d',
        'brief_date' => 'date:Y-m-d',
		
        'source_created_at' => 'datetime',
        'source_updated_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'source_deleted_at' => 'datetime',
		
        'is_source_deleted' => 'boolean',
		];
		
		public function source(): BelongsTo
		{
			return $this->belongsTo(
            ExternalSource::class,
            'external_source_id'
			);
		}
		
		public function links(): HasMany
		{
			return $this->hasMany(
            ProjectPermitLink::class,
            'permit_id'
			);
		}
		
		public function projects(): BelongsToMany
		{
			return $this->belongsToMany(
            Project::class,
            'dt_project_permit_links',
            'permit_id',
            'project_id'
			)
            ->wherePivot('is_active', true)
            ->withPivot([
			'id',
			'task_id',
			'linked_by_user_id',
			'linked_at',
			'notes',
			'is_active',
            ])
            ->withTimestamps();
		}
		
		public function tasks(): BelongsToMany
		{
			return $this->belongsToMany(
            ProjectTask::class,
            'dt_project_permit_links',
            'permit_id',
            'task_id'
			)
            ->wherePivot('is_active', true)
            ->wherePivotNotNull('task_id')
            ->withPivot([
			'id',
			'project_id',
			'linked_by_user_id',
			'linked_at',
			'notes',
			'is_active',
            ])
            ->withTimestamps();
		}
	}	