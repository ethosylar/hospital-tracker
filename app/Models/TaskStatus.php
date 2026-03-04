<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskStatus extends Model
{
    protected $table = 'st_task_statuses';

    protected $fillable = ['code', 'name', 'sort_order', 'is_active'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    // Optional relationship (useful later)
    public function tasks()
    {
        return $this->hasMany(ProjectTask::class, 'task_status_id');
    }
}
