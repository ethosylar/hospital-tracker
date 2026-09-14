<?php

namespace App\Support;

use App\Models\Department;
use App\Models\Site;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AgreementSiteRules
{
    public static function validate(int $siteId, ?int $departmentId, ?int $ownerUserId): void
    {
        /*
        |--------------------------------------------------------------------------
        | Site
        |--------------------------------------------------------------------------
        */

        $siteExists = Site::query()
            ->whereKey($siteId)
            ->where('is_active', true)
            ->exists();

        if (!$siteExists) {
            throw ValidationException::withMessages([
                'site_id' => [
                    'The selected Site does not exist or is inactive.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Department must belong to Agreement Site
        |--------------------------------------------------------------------------
        */

        if ($departmentId !== null) {
            $departmentOk = Department::query()
                ->whereKey($departmentId)
                ->where('site_id', $siteId)
                ->where('is_active', true)
                ->exists();

            if (!$departmentOk) {
                throw ValidationException::withMessages([
                    'department_id' => [
                        'The selected Department must be an active '
                            . 'Department from the same Site as the Agreement.',
                    ],
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Owner must have access to Agreement Site
        |--------------------------------------------------------------------------
        */

        if ($ownerUserId !== null) {
            $owner = User::query()
                ->find($ownerUserId);

            if (!$owner) {
                throw ValidationException::withMessages([
                    'owner_user_id' => [
                        'The selected Agreement owner does not exist.',
                    ],
                ]);
            }

            $ownerHasSite = $owner->hasPermission('system.all') || $owner->siteAccesses()
                ->where(
                    'site_id',
                    $siteId
                )
                ->where(
                    'is_active',
                    true
                )
                ->exists();

            if (!$ownerHasSite) {
                throw ValidationException::withMessages([
                    'owner_user_id' => [
                        'The selected Agreement owner does not have '
                            . 'access to the Agreement Site.',
                    ],
                ]);
            }
        }
    }
}
