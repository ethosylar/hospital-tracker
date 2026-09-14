<?php

namespace App\Support;

use App\Models\Agreement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AgreementAccess
{
    public static function visibleQuery(User $user): Builder
    {
        return self::applyViewScope(Agreement::query(), $user);
    }

    public static function applyViewScope(Builder $query, User $user, string $column = 'dt_agreements.site_id'): Builder
    {
        return SiteAccess::applyViewScope($query, $user, $column);
    }

    public static function canView(User $user, Agreement $agreement): bool
    {
        return SiteAccess::canViewSite($user, (int) $agreement->site_id);
    }

    public static function canManage(User $user, Agreement $agreement): bool
    {
        return SiteAccess::canManageSite($user, (int) $agreement->site_id);
    }

    public static function canManageSite(User $user, int $siteId): bool
    {
        return SiteAccess::canManageSite($user, $siteId);
    }
}
