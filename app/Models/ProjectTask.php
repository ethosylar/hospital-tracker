<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsToMany;
	
	class ProjectTask extends Model
	{
		protected $table = 'dt_project_tasks';
		
		protected $fillable = [
        'project_id',
        'name',
        'description',
        'task_color',
        'task_status_id',
        'actual_task_status_id',
        'progress',
        'start_date',
        'end_date',
        'actual_start_date',
        'actual_end_date',
        'duration',
        'assigned_to_user_id',
        'sort_order',
        'parent_task_id',
        'depends_on_task_id',
		'milestone_id',
		];
		
		protected $casts = [
        'task_status_id' => 'integer',
        'actual_task_status_id' => 'integer',
        'progress' => 'integer',
        'sort_order' => 'integer',
        'assigned_to_user_id' => 'integer',
        'parent_task_id' => 'integer',
        'depends_on_task_id' => 'integer',
        'duration' => 'integer',
		'milestone_id' => 'integer',
		
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'actual_start_date' => 'date:Y-m-d',
        'actual_end_date' => 'date:Y-m-d',
		];
		
		public function project()
		{
			return $this->belongsTo(Project::class, 'project_id');
		}
		
		public function status()
		{
			return $this->belongsTo(TaskStatus::class, 'task_status_id');
		}
		
		public function actualStatus()
		{
			return $this->belongsTo(TaskStatus::class, 'actual_task_status_id');
		}
		
		public function assignedTo()
		{
			return $this->belongsTo(\App\Models\User::class, 'assigned_to_user_id');
		}
		
		public function parentTask()
		{
			return $this->belongsTo(self::class, 'parent_task_id');
		}
		
		public function dependsOn()
		{
			return $this->belongsTo(self::class, 'depends_on_task_id');
		}
		
		public function milestone()
		{
			return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
		}
		
		public function files()
		{
			return $this->belongsToMany(\App\Models\StoredFile::class, 'dt_task_files', 'task_id', 'file_id')
			->withTimestamps();
		}
		
		public function budgetAllocations()
		{
			return $this->hasMany(\App\Models\ProjectBudgetAllocation::class, 'task_id');
		}
	}
