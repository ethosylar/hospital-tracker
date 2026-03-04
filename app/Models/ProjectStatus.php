<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectStatus extends Model
{
    protected $table = 'st_project_statuses';

    protected $fillable = ['code', 'name', 'sort_order', 'is_active'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class, 'project_status_id');
    }
}
