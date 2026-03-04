<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'lt_departments';

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
	
	public function users(): HasMany
    {
        return $this->hasMany(\App\Models\User::class, 'department_id');
    }
}
