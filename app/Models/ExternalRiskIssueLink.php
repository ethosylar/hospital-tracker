<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	
	class ExternalRiskIssueLink extends Model
	{
		protected $table = 'dt_external_risk_issue_links';
		
		protected $fillable = [
        'external_risk_issue_id',
        'project_id',
        'task_id',
        'milestone_id',
        'permit_id',
        'linked_by_user_id',
        'linked_at',
        'notes',
        'is_active',
		];
		
		protected $casts = [
        'external_risk_issue_id' => 'integer',
        'project_id' => 'integer',
        'task_id' => 'integer',
        'milestone_id' => 'integer',
        'permit_id' => 'integer',
        'linked_by_user_id' => 'integer',
        'linked_at' => 'datetime',
        'is_active' => 'boolean',
		];
		
		public function issue()
		{
			return $this->belongsTo(ExternalRiskIssue::class, 'external_risk_issue_id');
		}
		
		public function project()
		{
			return $this->belongsTo(Project::class, 'project_id');
		}
		
		public function task()
		{
			return $this->belongsTo(ProjectTask::class, 'task_id');
		}
		
		public function milestone()
		{
			return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
		}
		
		public function permit()
		{
			return $this->belongsTo(ExternalPermit::class, 'permit_id');
		}
		
		public function linkedBy()
		{
			return $this->belongsTo(User::class, 'linked_by_user_id');
		}
	}
