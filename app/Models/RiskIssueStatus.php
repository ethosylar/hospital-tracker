<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskIssueStatus extends Model
{
    protected $table = 'st_risk_issue_statuses';

    protected $fillable = ['code', 'name', 'sort_order', 'is_active'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    // Optional: if you have ExternalRiskIssue model
    public function externalRiskIssues()
    {
        return $this->hasMany(ExternalRiskIssue::class, 'risk_issue_status_id');
    }
}
