<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Priority extends Model
{
    protected $table = 'lt_priorities';

    protected $fillable = ['code', 'name', 'sort_order', 'is_active'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    // Optional: if you want relationship
    public function projects()
    {
        return $this->hasMany(Project::class, 'priority_id');
    }
}
