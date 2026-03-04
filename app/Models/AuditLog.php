<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'dt_audit_logs';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'action',
        'source',
        'performed_by_user_id',
        'performed_at',
        'changes',
    ];

    protected $casts = [
        'entity_id' => 'integer',
        'performed_by_user_id' => 'integer',
        'performed_at' => 'datetime',
        // If changes column is JSON type, you can cast as array:
        // 'changes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'performed_by_user_id');
    }
}
