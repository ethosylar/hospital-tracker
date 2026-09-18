<?php

namespace App\Support;

use App\Models\ExternalRiskIssue;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ExternalRiskIssueAccess
{
    public static function visibleQuery(User $user): Builder
    {
        return self::applyViewScope(ExternalRiskIssue::query(), $user);
    }

    public static function applyViewScope(Builder $query, User $user, string $column = 'dt_external_risk_issues.site_id'): Builder
    {
        return SiteAccess::applyViewScope($query, $user, $column);
    }

    public static function canView(User $user, ExternalRiskIssue $issue): bool
    {
        return SiteAccess::canViewSite($user, (int) $issue->site_id);
    }

    public static function canManage(User $user, ExternalRiskIssue $issue): bool
    {
        return SiteAccess::canManageSite($user, (int) $issue->site_id);
    }

    public static function canManageSite(User $user, int $siteId): bool
    {
        return SiteAccess::canManageSite($user, $siteId);
    }
}
