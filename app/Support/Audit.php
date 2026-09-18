<?php

namespace App\Support;

use App\Models\AuditLog;

class Audit
{
    public static function log(?int $userId, string $entityType, int $entityId, string $action, array $changes = [], string $source = 'API', ?int $siteId = null): AuditLog
    {
        $entityType = strtoupper(trim($entityType));
        $action = strtoupper(trim($action));
        $source = strtoupper(trim($source));
        /*
        |--------------------------------------------------------------------------
        | Resolve Site automatically
        |--------------------------------------------------------------------------
        */

        if ($siteId === null) {
            $siteId = app(AuditSiteResolver::class)->resolve($entityType, $entityId, $changes);
        }

        return AuditLog::query()->create([
            'site_id' => $siteId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'changes' => $changes,
            'performed_by_user_id' => $userId,
            'source' => $source !== '' ? $source : 'API',
            'performed_at' => now(),
        ]);
    }
}
