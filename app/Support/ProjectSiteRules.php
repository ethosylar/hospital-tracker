<?php

namespace App\Support;

use App\Models\Department;
use App\Models\Site;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ProjectSiteRules
{
    public static function validate(
        int $siteId,
        ?int $departmentId,
        ?int $ownerUserId
    ): void {
        if (
            !Site::query()
                ->whereKey($siteId)
                ->where('is_active', true)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'site_id' => [
                    'The selected site does not exist or is inactive.',
                ],
            ]);
        }

        if ($departmentId !== null) {
            $departmentOk = Department::query()
                ->whereKey($departmentId)
                ->where('site_id', $siteId)
                ->where('is_active', true)
                ->exists();

            if (!$departmentOk) {
                throw ValidationException::withMessages([
                    'department_id' => [
                        'The selected department must be an active department from the same site as the project.',
                    ],
                ]);
            }
        }

        if ($ownerUserId !== null) {
            $owner = User::query()->find($ownerUserId);

            if (!$owner) {
                throw ValidationException::withMessages([
                    'owner_user_id' => [
                        'The selected project owner does not exist.',
                    ],
                ]);
            }

            $ownerHasSite = $owner->hasPermission('system.all')
                || $owner->siteAccesses()
                ->where('site_id', $siteId)
                ->where('is_active', true)
                ->exists();

            if (!$ownerHasSite) {
                throw ValidationException::withMessages([
                    'owner_user_id' => [
                        'The selected project owner does not have access to the project site.',
                    ],
                ]);
            }
        }
    }
}
