<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $table = 'lt_roles';

    protected $fillable = ['code', 'name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // If you have pivot table dt_user_roles
    public function users()
    {
        return $this->belongsToMany(User::class, 'dt_user_roles', 'role_id', 'user_id')->withTimestamps();
    }
}
