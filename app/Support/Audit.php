<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Audit
{
    public static function log(?int $userId, string $entityType, int $entityId, string $action, array $changes = [], string $source = 'API'): void
    {
        DB::table('dt_audit_logs')->insert([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'changes' => empty($changes) ? null : json_encode($changes),
            'performed_by_user_id' => $userId,
            'source' => $source,
            'performed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}