<?php

namespace App\Support;

use App\Models\Site;
use App\Models\User;
use App\Models\UserSite;
use Illuminate\Database\Eloquent\Builder;

class SiteAccess
{
    public const LEVEL_VIEW = UserSite::LEVEL_VIEW;
    public const LEVEL_MANAGE = UserSite::LEVEL_MANAGE;
    public const LEVEL_ADMIN = UserSite::LEVEL_ADMIN;

    /**
     * Returns every site visible to the user.
     * system.all bypasses site assignment and can access all active sites.
     */
    public static function viewSiteIds(User $user): array
    {
        if ($user->hasPermission('system.all')) {
            return Site::query()
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all();
        }

        return self::siteIdsForLevels(
            $user,
            [
                self::LEVEL_VIEW,
                self::LEVEL_MANAGE,
                self::LEVEL_ADMIN,
            ]
        );
    }

    public static function manageSiteIds(User $user): array
    {
        if ($user->hasPermission('system.all')) {
            return Site::query()
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all();
        }

        return self::siteIdsForLevels(
            $user,
            [self::LEVEL_MANAGE, self::LEVEL_ADMIN]
        );
    }

    public static function adminSiteIds(User $user): array
    {
        if ($user->hasPermission('system.all')) {
            return Site::query()
                ->where('is_active', true)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all();
        }

        return self::siteIdsForLevels($user, [self::LEVEL_ADMIN]);
    }

    public static function canViewSite(User $user, int $siteId): bool
    {
        if ($user->hasPermission('system.all')) {
            return true;
        }

        return $user
            ->siteAccesses()
            ->where('site_id', $siteId)
            ->where('is_active', true)
            ->whereIn('access_level', ['VIEW', 'MANAGE',])
            ->exists();
    }

    public static function canManageSite(User $user, int $siteId): bool
    {
        if ($user->hasPermission('system.all')) {
            return true;
        }

        return $user
            ->siteAccesses()
            ->where('site_id', $siteId)
            ->where('is_active', true)
            ->where('access_level', 'MANAGE')
            ->exists();
    }

    public static function canAdminSite(User $user, int|Site $site): bool
    {
        if ($user->hasPermission('system.all')) {
            return true;
        }

        $siteId = $site instanceof Site ? (int) $site->id : (int) $site;
        return in_array($siteId, self::adminSiteIds($user), true);
    }

    /**
     * Use this later on Project/Agreement/Department queries:
     *
     * SiteAccess::applyViewScope($query, $request->user());
     * SiteAccess::applyViewScope($query, $request->user(), 'dt_projects.site_id');
     */
    // public static function applyViewScope(Builder $query, User $user, string $column = 'site_id'): Builder
    // {
    //     if ($user->hasPermission('system.all')) {
    //         return $query;
    //     }

    //     $siteIds = self::viewSiteIds($user);

    //     if (empty($siteIds)) {
    //         return $query->whereRaw('1 = 0');
    //     }

    //     return $query->whereIn($column, $siteIds);
    // }

    public static function applyViewScope(Builder $query, User $user, string $column = 'site_id'): Builder
    {
        if ($user->hasPermission('system.all')) {
            return $query;
        }

        $siteIds = self::viewSiteIds($user);

        if (empty($siteIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $siteIds);
    }

    public static function applyManageScope(Builder $query, User $user, string $column = 'site_id'): Builder
    {
        if ($user->hasPermission('system.all')) {
            return $query;
        }

        $siteIds = self::manageSiteIds($user);

        if (empty($siteIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $siteIds);
    }

    private static function siteIdsForLevels(User $user, array $levels): array
    {
        return UserSite::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereIn('access_level', $levels)
            ->whereHas('site', function (Builder $query) {
                $query->where('is_active', true);
            })
            ->pluck('site_id')
            ->map(fn($id) => (int) $id)
            ->all();
    }
}
