<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    protected $table = 'dt_projects';

    protected $fillable = [
        'code',
        'name',
        'description',
        'department_id',
        'owner_user_id',
        'sponsor',
        'project_status_id',
        'priority_id',
        'progress',
        'start_date',
        'target_end_date',
        'actual_end_date',
    ];

    protected $casts = [
        'progress' => 'integer',
        'start_date' => 'date',
        'target_end_date' => 'date',
        'actual_end_date' => 'date',
    ];

    public function milestones()
    {
        return $this->hasMany(ProjectMilestone::class, 'project_id');
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class, 'project_id');
    }
	
	public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function status()
    {
        return $this->belongsTo(ProjectStatus::class, 'project_status_id');
    }

    public function priority()
    {
        return $this->belongsTo(Priority::class, 'priority_id');
    }

    public function owner()
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_user_id');
    }
	
	public function files(): BelongsToMany
	{
		return $this->belongsToMany(\App\Models\StoredFile::class, 'dt_project_files', 'project_id','file_id')
		->withTimestamps();
	}
}
