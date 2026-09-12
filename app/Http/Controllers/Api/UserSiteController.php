<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignUserSitesRequest;
use App\Http\Resources\UserSiteResource;
use App\Models\User;
use App\Models\UserSite;
use App\Support\ApiErrorCode;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class UserSiteController extends Controller
{
    /**
     * Phase 1A: user-site administration is intentionally global-admin only.
     * Site Admin delegation should be enabled only after Departments, Projects,
     * Agreements and User management have all become site-scoped.
     */
    public function indexForUser(Request $request, User $user)
    {
        if (!$request->user()->hasPermission('system.all')) {
            return ApiResponse::error(
                ApiErrorCode::USER_SITE_MANAGE_FORBIDDEN,
                'Only the Super Admin can manage user site assignments at this stage.',
                [],
                403
            );
        }

        return UserSiteResource::collection(
            UserSite::query()
                ->where('user_id', $user->id)
                ->with('site')
                ->orderByDesc('is_primary')
                ->orderBy('site_id')
                ->get()
        );
    }

    public function sync(
        AssignUserSitesRequest $request,
        User $user
    ) {
        if (!$request->user()->hasPermission('system.all')) {
            return ApiResponse::error(
                ApiErrorCode::USER_SITE_MANAGE_FORBIDDEN,
                'Only the Super Admin can manage user site assignments at this stage.',
                [],
                403
            );
        }

        $data = $request->validated();
        $primarySiteId = (int) $data['primary_site_id'];

        $siteRows = collect($data['sites'])
            ->map(function (array $row) use ($primarySiteId) {
                $siteId = (int) $row['site_id'];

                return [
                    'site_id' => $siteId,
                    'access_level' => $row['access_level'],
                    'is_primary' => $siteId === $primarySiteId,
                    'is_active' => true,
                ];
            });

        if (!$siteRows->contains('is_primary', true)) {
            return ApiResponse::error(
                ApiErrorCode::USER_SITE_INVALID_PRIMARY,
                'The primary site must be included in the assigned sites.',
                [],
                422
            );
        }

        $before = UserSite::query()
            ->where('user_id', $user->id)
            ->with('site:id,code,name')
            ->get()
            ->map(fn(UserSite $assignment) => [
                'site_id' => (int) $assignment->site_id,
                'site_code' => $assignment->site?->code,
                'access_level' => $assignment->access_level,
                'is_primary' => (bool) $assignment->is_primary,
                'is_active' => (bool) $assignment->is_active,
            ])
            ->values()
            ->all();

        try {
            DB::transaction(function () use ($user, $siteRows) {
                UserSite::query()
                    ->where('user_id', $user->id)
                    ->delete();

                $now = now();

                $rows = $siteRows
                    ->map(fn(array $row) => [
                        'user_id' => (int) $user->id,
                        'site_id' => $row['site_id'],
                        'access_level' => $row['access_level'],
                        'is_primary' => $row['is_primary'],
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all();

                DB::table('dt_user_sites')->insert($rows);
            });

            $afterModels = UserSite::query()
                ->where('user_id', $user->id)
                ->with('site')
                ->orderByDesc('is_primary')
                ->orderBy('site_id')
                ->get();

            $after = $afterModels
                ->map(fn(UserSite $assignment) => [
                    'site_id' => (int) $assignment->site_id,
                    'site_code' => $assignment->site?->code,
                    'access_level' => $assignment->access_level,
                    'is_primary' => (bool) $assignment->is_primary,
                    'is_active' => (bool) $assignment->is_active,
                ])
                ->values()
                ->all();

            \App\Support\Audit::log(
                $request->user()->id,
                'USER_SITE_ACCESS',
                (int) $user->id,
                'UPDATE',
                [
                    'before' => $before,
                    'after' => $after,
                ]
            );

            return UserSiteResource::collection($afterModels);
        } catch (Throwable $e) {
            report($e);

            return ApiResponse::error(
                ApiErrorCode::USER_SITE_ASSIGNMENT_FAILED,
                'Failed to update user site assignments.',
                $this->errorDetails($e),
                500
            );
        }
    }

    private function errorDetails(Throwable $e): array
    {
        if (!config('app.debug')) {
            return [];
        }

        return [
            'exception' => $e->getMessage(),
            'exception_class' => get_class($e),
        ];
    }
}
