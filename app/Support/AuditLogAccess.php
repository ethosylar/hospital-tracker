<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AuditLogAccess
{
    public static function visibleQuery(User $user): Builder
    {
        return self::applyViewScope(AuditLog::query(), $user);
    }

    public static function applyViewScope(Builder $query, User $user, string $column = 'dt_audit_logs.site_id'): Builder
    {
        /*
        |--------------------------------------------------------------------------
        | system.all
        |--------------------------------------------------------------------------
        |
        | SiteAccess bypass means:
        |
        | - all Site logs
        | - NULL/global logs
        |--------------------------------------------------------------------------
        */

        if ($user->hasPermission('system.all')) {
            return $query;
        }

        /*
        |--------------------------------------------------------------------------
        | Normal Auditor
        |--------------------------------------------------------------------------
        |
        | SiteAccess applies VIEW access.
        |
        | Because global rows have site_id = NULL, they are naturally excluded.
        |--------------------------------------------------------------------------
        */

        return SiteAccess::applyViewScope($query, $user, $column);
    }

    public static function canView(User $user, AuditLog $auditLog): bool
    {
        if ($user->hasPermission('system.all')) {
            return true;
        }

        /*
         * Global system logs are Admin-only.
         */
        if ($auditLog->site_id === null) {
            return false;
        }

        return SiteAccess::canViewSite($user, (int) $auditLog->site_id);
    }
}
