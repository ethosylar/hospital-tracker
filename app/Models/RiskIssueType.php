<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskIssueType extends Model
{
    protected $table = 'lt_risk_issue_types';

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
