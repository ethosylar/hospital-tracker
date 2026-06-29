<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	
	class IntegrationSyncRun extends Model
	{
		protected $table = 'dt_integration_sync_runs';
		
		protected $fillable = [
        'external_source_id',
        'integration_code',
        'sync_type',
        'status',
		
        'started_at',
        'completed_at',
		
        'fetched_count',
        'created_count',
        'updated_count',
        'unchanged_count',
        'deleted_count',
        'failed_count',
		
        'cursor_from',
        'cursor_to',
        'error_message',
		
        'triggered_by_user_id',
		];
		
		protected $casts = [
        'external_source_id' => 'integer',
        'triggered_by_user_id' => 'integer',
		
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
		
        'fetched_count' => 'integer',
        'created_count' => 'integer',
        'updated_count' => 'integer',
        'unchanged_count' => 'integer',
        'deleted_count' => 'integer',
        'failed_count' => 'integer',
		];
		
		public function source(): BelongsTo
		{
			return $this->belongsTo(
            ExternalSource::class,
            'external_source_id'
			);
		}
		
		public function triggeredBy(): BelongsTo
		{
			return $this->belongsTo(
            User::class,
            'triggered_by_user_id'
			);
		}
	}	