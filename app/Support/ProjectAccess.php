<?php

namespace App\Support;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ProjectAccess
{
    public static function visibleQuery(User $user): Builder
    {
        return self::applyViewScope(
            Project::query(),
            $user
        );
    }

    public static function applyViewScope(
        Builder $query,
        User $user,
        string $column = 'dt_projects.site_id'
    ): Builder {
        return SiteAccess::applyViewScope(
            $query,
            $user,
            $column
        );
    }

    public static function canView(
        User $user,
        Project $project
    ): bool {
        return SiteAccess::canViewSite(
            $user,
            (int) $project->site_id
        );
    }

    public static function canManage(
        User $user,
        Project $project
    ): bool {
        return SiteAccess::canManageSite(
            $user,
            (int) $project->site_id
        );
    }

    public static function canManageSite(
        User $user,
        int $siteId
    ): bool {
        return SiteAccess::canManageSite(
            $user,
            $siteId
        );
    }
}
