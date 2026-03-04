<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Severity extends Model
{
    protected $table = 'st_severities';

    protected $fillable = ['code', 'name', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
