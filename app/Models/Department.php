<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'lt_departments';

    protected $fillable = [
        'site_id',
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Department $department) {
            if ($department->code !== null) {
                $department->code = strtoupper(trim((string) $department->code));
            }

            if ($department->name !== null) {
                $department->name = trim((string) $department->name);
            }
        });
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'department_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'department_id');
    }

    public function agreements()
    {
        return $this->hasMany(Agreement::class, 'department_id');
    }
}
