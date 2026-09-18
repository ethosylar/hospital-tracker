<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'dt_audit_logs';

    protected $fillable = [
        'site_id',
        'entity_type',
        'entity_id',
        'action',
        'changes',
        'performed_by_user_id',
        'source',
        'performed_at',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'entity_id' => 'integer',
        'performed_by_user_id' => 'integer',
        'changes' => 'array',
        'performed_at' => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(
            Site::class,
            'site_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'performed_by_user_id'
        );
    }
}
