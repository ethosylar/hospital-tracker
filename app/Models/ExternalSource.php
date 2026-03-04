<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalSource extends Model
{
    protected $table = 'lt_external_sources';

    protected $fillable = [
        'code',
        'name',
        'base_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
