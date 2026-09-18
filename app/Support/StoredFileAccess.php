<?php

namespace App\Support;

use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StoredFileAccess
{
    public static function visibleQuery(User $user): Builder
    {
        return self::applyViewScope(StoredFile::query(), $user);
    }

    public static function applyViewScope(Builder $query, User $user, string $column = 'dt_files.site_id'): Builder
    {
        return SiteAccess::applyViewScope($query, $user, $column);
    }

    public static function canView(User $user, StoredFile $file): bool
    {
        return SiteAccess::canViewSite($user, (int) $file->site_id);
    }

    public static function canManage(User $user, StoredFile $file): bool
    {
        return SiteAccess::canManageSite($user, (int) $file->site_id);
    }

    public static function canManageSite(User $user, int $siteId): bool
    {
        return SiteAccess::canManageSite($user, $siteId);
    }
}
