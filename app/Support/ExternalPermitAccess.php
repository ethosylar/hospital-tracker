<?php

namespace App\Support;

use App\Models\ExternalPermit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ExternalPermitAccess
{
    public static function visibleQuery(User $user): Builder
    {
        return self::applyViewScope(ExternalPermit::query(), $user);
    }

    public static function applyViewScope(Builder $query, User $user, string $column = 'dt_external_permits.site_id'): Builder
    {
        return SiteAccess::applyViewScope($query, $user, $column);
    }

    public static function canView(User $user, ExternalPermit $permit): bool
    {
        return SiteAccess::canViewSite($user, (int) $permit->site_id);
    }

    public static function canManage(User $user, ExternalPermit $permit): bool
    {
        return SiteAccess::canManageSite($user, (int) $permit->site_id);
    }
}
