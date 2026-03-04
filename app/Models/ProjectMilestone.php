<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	
	class ProjectMilestone extends Model
	{
		protected $table = 'dt_project_milestones';
		
		protected $fillable = [
        'project_id',
        'name',
        'milestone_date',
        'status',
		];
		
		protected $casts = [
        'milestone_date' => 'date',
		];
		
		public function project()
		{
			return $this->belongsTo(Project::class, 'project_id');
		}
		
		public function tasks()
		{
			return $this->hasMany(ProjectTask::class, 'milestone_id');
		}
	}
