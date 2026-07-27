<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	
	class ExternalRiskIssue extends Model
	{
		protected $table = 'dt_external_risk_issues';
		
		protected $fillable = [
        'external_source_id',
        'external_id',
        'project_id',
        'type_id',
        'title',
        'description',
        'severity_id',
        'risk_issue_status_id',
        'owner',
        'source_created_at',
        'source_updated_at',
        'last_synced_at',
        'raw_payload',
		];
		
		protected $casts = [
        'external_source_id' => 'integer',
        'project_id' => 'integer',
        'type_id' => 'integer',
        'severity_id' => 'integer',
        'risk_issue_status_id' => 'integer',
        'source_created_at' => 'datetime',
        'source_updated_at' => 'datetime',
        'last_synced_at' => 'datetime',
		];
		
		public function externalSource()
		{
			return $this->belongsTo(ExternalSource::class, 'external_source_id');
		}
		
		public function project()
		{
			return $this->belongsTo(Project::class, 'project_id');
		}
		
		public function type()
		{
			return $this->belongsTo(RiskIssueType::class, 'type_id');
		}
		
		public function severity()
		{
			return $this->belongsTo(Severity::class, 'severity_id');
		}
		
		public function status()
		{
			return $this->belongsTo(RiskIssueStatus::class, 'risk_issue_status_id');
		}
		
		public function links()
		{
			return $this->hasMany(ExternalRiskIssueLink::class, 'external_risk_issue_id');
		}
		
		public function activeLinks()
		{
			return $this->hasMany(ExternalRiskIssueLink::class, 'external_risk_issue_id')
            ->where('is_active', true);
		}
	}
