<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\ExternalSource;
use App\Models\Site;
use App\Models\User;
use App\Support\SiteAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LookupController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | General lookups
    |--------------------------------------------------------------------------
    |
    | Optional:
    |
    | GET /api/lookups
    | GET /api/lookups?site_id=1
    |
    | Without site_id:
    |     Site-owned lookups contain records from all Sites the user may VIEW.
    |
    | With site_id:
    |     Site-owned lookups contain records only from that Site.
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $data = $request->validate([
            'site_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'lt_sites',
                    'id'
                )->where(
                    fn($query) =>
                    $query->where('is_active', true)
                ),
            ],
        ]);

        $user = $request->user();
        $accessibleSiteIds = $this->accessibleSiteIds($user);

        $selectedSiteId = !empty($data['site_id']) ? (int) $data['site_id'] : null;

        /*
        |--------------------------------------------------------------------------
        | Explicit Site must be visible
        |--------------------------------------------------------------------------
        */

        if ($selectedSiteId !== null && !SiteAccess::canViewSite($user, $selectedSiteId)) {
            /*
             * Use 404 to avoid revealing inaccessible Site existence.
             */
            abort(404);
        }

        $scopeSiteIds = $selectedSiteId !== null ? collect([$selectedSiteId,]) : $accessibleSiteIds;

        /*
        |--------------------------------------------------------------------------
        | Sites
        |--------------------------------------------------------------------------
        */

        $sites = Site::query()
            ->where('is_active', true)
            ->whereIn('id', $accessibleSiteIds)
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'short_name',
                'site_type',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Departments
        |--------------------------------------------------------------------------
        */

        $departments =
            Department::query()
            ->where('is_active', true)
            ->whereIn('site_id', $scopeSiteIds)
            ->orderBy('name')
            ->get([
                'id',
                'site_id',
                'code',
                'name',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Project / Agreement owners
        |--------------------------------------------------------------------------
        |
        | A valid owner is:
        |
        | - assigned active access to one of the requested Sites
        | OR
        | - a system.all user
        |
        | This matches ProjectSiteRules / AgreementSiteRules.
        |--------------------------------------------------------------------------
        */

        $owners =
            $this->ownersQuery($scopeSiteIds)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'department_id',
            ]);

        /*
        |--------------------------------------------------------------------------
        | External Sources
        |--------------------------------------------------------------------------
        */

        $externalSources =
            ExternalSource::query()
            ->where('is_active', true)
            ->whereIn('site_id', $scopeSiteIds)
            ->orderBy('name')
            ->get([
                'id',
                'site_id',
                'code',
                'name',
                'base_url',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Global master data
        |--------------------------------------------------------------------------
        |
        | These are intentionally NOT Site-scoped.
        |--------------------------------------------------------------------------
        */

        $priorities =
            DB::table('lt_priorities')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get([
                'id',
                'code',
                'name',
                'sort_order',
            ]);

        $projectStatuses =
            DB::table('st_project_statuses')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get([
                'id',
                'code',
                'name',
                'sort_order',
            ]);

        $taskStatuses =
            DB::table('st_task_statuses')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get([
                'id',
                'code',
                'name',
                'sort_order',
            ]);

        $riskIssueStatuses =
            DB::table('st_risk_issue_statuses')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get([
                'id',
                'code',
                'name',
                'sort_order',
            ]);

        $severities =
            DB::table('st_severities')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get([
                'id',
                'code',
                'name',
                'sort_order',
            ]);

        $riskIssueTypes =
            DB::table('lt_risk_issue_types')
            ->where('is_active', 1)
            ->orderBy('id')
            ->get([
                'id',
                'code',
                'name',
            ]);

        $projectCategories =
            DB::table('lt_project_categories')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
            ]);

        return response()->json([
            'selected_site_id' => $selectedSiteId,
            'sites' => $sites,
            'owners' => $owners,
            'external_sources' => $externalSources,
            'departments' => $departments,
            'priorities' => $priorities,
            'project_statuses' => $projectStatuses,
            'task_statuses' => $taskStatuses,
            'risk_issue_statuses' => $riskIssueStatuses,
            'severities' => $severities,
            'risk_issue_types' => $riskIssueTypes,
            'project_categories' => $projectCategories,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | User Management Lookups
    |--------------------------------------------------------------------------
    */

    public function userManagement(Request $request)
    {
        $data = $request->validate([
            'site_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'lt_sites',
                    'id'
                )->where(
                    fn($query) =>
                    $query->where('is_active', true)
                ),
            ],
        ]);

        $user = $request->user();
        $accessibleSiteIds = $this->accessibleSiteIds($user);
        $selectedSiteId = !empty($data['site_id']) ? (int) $data['site_id'] : null;

        if ($selectedSiteId !== null && !SiteAccess::canViewSite($user, $selectedSiteId)) {
            abort(404);
        }

        $scopeSiteIds = $selectedSiteId !== null ? collect([$selectedSiteId,]) : $accessibleSiteIds;

        /*
        |--------------------------------------------------------------------------
        | Sites
        |--------------------------------------------------------------------------
        */

        $sites =
            Site::query()
            ->where('is_active', true)
            ->whereIn('id', $accessibleSiteIds)
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'short_name',
                'site_type',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Departments
        |--------------------------------------------------------------------------
        */

        $departments =
            Department::query()
            ->where('is_active', true)
            ->whereIn('site_id', $scopeSiteIds)
            ->orderBy('name')
            ->get([
                'id',
                'site_id',
                'code',
                'name',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Global permissions / roles
        |--------------------------------------------------------------------------
        */

        $permissions =
            DB::table('lt_permissions')
            ->where('is_active', 1)
            ->orderBy('module')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
                'module',
                'description',
                'sort_order',
            ]);

        $roles =
            DB::table('lt_roles')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get([
                'id',
                'code',
                'name',
            ]);

        $rolePermissions =
            DB::table('lt_role_permissions as rp')
            ->join('lt_roles as r', 'r.id', '=', 'rp.role_id')
            ->join('lt_permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('r.is_active', 1)
            ->where('p.is_active', 1)
            ->orderBy('r.name')
            ->orderBy('p.module')
            ->orderBy('p.sort_order')
            ->orderBy('p.name')
            ->get([
                'rp.role_id',
                'rp.permission_id',
                'p.code as permission_code',
                'p.name as permission_name',
                'p.module as permission_module',
            ]);

        $permissionsByRole =
            $rolePermissions
            ->groupBy('role_id')
            ->map(
                function ($items) {
                    return $items
                        ->map(
                            function ($item) {
                                return [
                                    'id' => (int) $item->permission_id,
                                    'code' => $item->permission_code,
                                    'name' => $item->permission_name,
                                    'module' => $item->permission_module,
                                ];
                            }
                        )
                        ->values();
                }
            );

        $rolesWithPermissions =
            $roles->map(
                function ($role) use (
                    $permissionsByRole
                ) {
                    return [
                        'id' => (int) $role->id,
                        'code' => $role->code,
                        'name' => $role->name,
                        'permissions' => $permissionsByRole->get($role->id, collect())->values(),
                    ];
                }
            );

        $permissionModules =
            $permissions
            ->pluck('module')
            ->filter()
            ->unique()
            ->values();

        return response()->json([
            'selected_site_id' => $selectedSiteId,
            'sites' => $sites,
            'site_access_levels' => [
                [
                    'code' => 'VIEW',
                    'name' => 'View',
                ],
                [
                    'code' => 'MANAGE',
                    'name' => 'Manage',
                ],
            ],

            'departments' => $departments,
            'roles' => $rolesWithPermissions,
            'permissions' => $permissions,
            'permission_modules' => $permissionModules,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessible Site IDs
    |--------------------------------------------------------------------------
    */

    private function accessibleSiteIds(User $user): Collection
    {
        /*
         * Full system users see every active Site.
         */
        if ($user->hasPermission('system.all')) {
            return Site::query()
                ->where('is_active', true)
                ->orderBy('id')
                ->pluck('id')
                ->map(fn($id) => (int) $id);
        }

        /*
         * Get active user Site assignments first.
         */
        $assignedIds =
            $user
            ->siteAccesses()
            ->where('is_active', true)
            ->pluck('site_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        /*
         * Exclude Sites that themselves are inactive.
         */
        return Site::query()
            ->where('is_active', true)
            ->whereIn('id', $assignedIds)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn($id) => (int) $id);
    }

    /*
    |--------------------------------------------------------------------------
    | Owner Query
    |--------------------------------------------------------------------------
    */

    private function ownersQuery(Collection $siteIds): Builder
    {
        $ids = $siteIds
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        return User::query()
            ->where(
                function (Builder $query) use (
                    $ids
                ) {
                    /*
                     * Normal Site-assigned users.
                     */
                    $query->whereHas(
                        'siteAccesses',
                        function ($siteQuery) use (
                            $ids
                        ) {
                            $siteQuery
                                ->where('is_active', true)
                                ->whereIn('site_id', $ids);
                        }
                    );

                    /*
                     * system.all is considered valid for every Site,
                     * consistent with ProjectSiteRules.
                     */
                    $query->orWhereHas(
                        'roles.permissions',
                        function ($permission) {
                            $permission
                                ->where('code', 'system.all')
                                ->where(
                                    'is_active',
                                    true
                                );
                        }
                    );
                }
            );
    }
}
