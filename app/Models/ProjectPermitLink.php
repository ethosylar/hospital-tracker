<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	
	class ProjectPermitLink extends Model
	{
		protected $table = 'dt_project_permit_links';
		
		protected $fillable = [
        'permit_id',
        'project_id',
        'task_id',
        'linked_by_user_id',
        'linked_at',
        'notes',
        'is_active',
		];
		
		protected $casts = [
        'permit_id' => 'integer',
        'project_id' => 'integer',
        'task_id' => 'integer',
        'linked_by_user_id' => 'integer',
        'linked_at' => 'datetime',
        'is_active' => 'boolean',
		];
		
		public function permit()
		{
			return $this->belongsTo(ExternalPermit::class, 'permit_id');
		}
		
		public function project()
		{
			return $this->belongsTo(Project::class, 'project_id');
		}
		
		public function task()
		{
			return $this->belongsTo(ProjectTask::class, 'task_id');
		}
		
		public function linkedBy()
		{
			return $this->belongsTo(User::class, 'linked_by_user_id');
		}
	}		