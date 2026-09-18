<?php

namespace Tests\Feature\MultiSite;

use App\Models\Agreement;
use App\Models\Counterparty;
use App\Models\Department;
use App\Models\ExternalPermit;
use App\Models\ExternalRiskIssue;
use App\Models\ExternalSource;
use App\Models\IntegrationSyncRun;
use App\Models\Permission;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Models\Role;
use App\Models\Site;
use App\Models\StoredFile;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

abstract class MultiSiteTestCase extends TestCase
{
    use DatabaseTransactions;

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    protected function signIn(
        User $user
    ): void {
        Sanctum::actingAs(
            $user->fresh(),
            ['*']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Unique token
    |--------------------------------------------------------------------------
    */

    protected function token(
        string $prefix = 'TST'
    ): string {
        return strtoupper(
            $prefix . '_' . Str::random(10)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Sites
    |--------------------------------------------------------------------------
    */

    protected function createSite(
        string $prefix = 'SITE'
    ): Site {
        $code = $this->token(
            $prefix
        );

        return Site::forceCreate([
            'code' => $code,

            'name' =>
            'Phase 1J ' . $code,

            'short_name' =>
            $code,

            'site_type' =>
            'HOSPITAL',

            'is_active' =>
            true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Departments
    |--------------------------------------------------------------------------
    */

    protected function createDepartment(
        Site $site,
        string $prefix = 'DEPT'
    ): Department {
        $code = $this->token(
            $prefix
        );

        return Department::forceCreate([
            'site_id' =>
            (int) $site->id,

            'code' =>
            $code,

            'name' =>
            'Phase 1J ' . $code,

            'is_active' =>
            true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Permission
    |--------------------------------------------------------------------------
    */

    protected function ensurePermission(
        string $code
    ): Permission {
        $permission =
            Permission::query()
            ->where(
                'code',
                $code
            )
            ->first();

        if ($permission) {
            $permission->forceFill([
                'is_active' =>
                true,
            ])->save();

            return $permission;
        }

        return Permission::forceCreate([
            'code' =>
            $code,

            'name' =>
            'Test ' . $code,

            'module' =>
            'Phase 1J',

            'description' =>
            'Created by automated Multi-Site tests.',

            'sort_order' =>
            999,

            'is_active' =>
            true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    |
    | $siteGrants example:
    |
    | [
    |     [$klg, 'VIEW'],
    |     [$amp, 'MANAGE'],
    | ]
    |--------------------------------------------------------------------------
    */

    protected function createUser(
        array $permissions = [],
        array $siteGrants = [],
        ?Department $department = null
    ): User {
        $token =
            strtolower(
                Str::random(12)
            );

        $user = User::forceCreate([
            'name' =>
            'Phase 1J User ' . $token,

            'username' =>
            'tst_' . $token,

            'email' =>
            'phase1j_' . $token
                . '@example.test',

            'password' =>
            Hash::make(
                'password'
            ),

            'department_id' =>
            $department?->id,
        ]);

        $roleCode =
            'TST_'
            . strtoupper(
                Str::random(12)
            );

        $role =
            Role::forceCreate([
                'code' =>
                $roleCode,

                'name' =>
                'Phase 1J Role '
                    . $roleCode,

                'is_active' =>
                true,

                'is_system_role' =>
                false,
            ]);

        $permissionIds =
            collect(
                $permissions
            )
            ->map(
                fn(string $code) =>
                (int) $this
                    ->ensurePermission(
                        $code
                    )
                    ->id
            )
            ->unique()
            ->values()
            ->all();

        $role->permissions()
            ->sync(
                $permissionIds
            );

        $user->roles()
            ->sync([
                $role->id,
            ]);

        foreach (
            $siteGrants
            as $grant
        ) {
            /** @var Site $site */
            $site =
                $grant[0];

            $accessLevel =
                strtoupper(
                    $grant[1]
                );

            $this->grantSite(
                $user,
                $site,
                $accessLevel
            );
        }

        return $user->fresh();
    }

    protected function grantSite(
        User $user,
        Site $site,
        string $accessLevel
    ): void {
        $values = [
            'access_level' =>
            strtoupper(
                $accessLevel
            ),

            'is_active' =>
            true,
        ];

        if (
            Schema::hasColumn(
                'dt_user_sites',
                'created_at'
            )
        ) {
            $values['created_at'] =
                now();
        }

        if (
            Schema::hasColumn(
                'dt_user_sites',
                'updated_at'
            )
        ) {
            $values['updated_at'] =
                now();
        }

        DB::table(
            'dt_user_sites'
        )->updateOrInsert(
            [
                'user_id' =>
                (int) $user->id,

                'site_id' =>
                (int) $site->id,
            ],
            $values
        );

        $user->unsetRelation(
            'siteAccesses'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Generic lookup helper
    |--------------------------------------------------------------------------
    */

    protected function ensureLookup(
        string $table,
        string $code,
        string $name,
        array $extra = []
    ): int {
        $existingId =
            DB::table($table)
            ->where(
                'code',
                $code
            )
            ->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        $data = [
            'code' =>
            $code,

            'name' =>
            $name,

            ...$extra,
        ];

        if (
            Schema::hasColumn(
                $table,
                'sort_order'
            )
            && !array_key_exists(
                'sort_order',
                $data
            )
        ) {
            $data['sort_order'] =
                999;
        }

        if (
            Schema::hasColumn(
                $table,
                'is_active'
            )
            && !array_key_exists(
                'is_active',
                $data
            )
        ) {
            $data['is_active'] =
                true;
        }

        if (
            Schema::hasColumn(
                $table,
                'created_at'
            )
        ) {
            $data['created_at'] =
                now();
        }

        if (
            Schema::hasColumn(
                $table,
                'updated_at'
            )
        ) {
            $data['updated_at'] =
                now();
        }

        return (int) DB::table(
            $table
        )->insertGetId(
            $data
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Project lookup IDs
    |--------------------------------------------------------------------------
    */

    protected function projectStatusId(): int
    {
        $id =
            DB::table(
                'st_project_statuses'
            )
            ->where(
                'is_active',
                true
            )
            ->value('id');

        return $id
            ? (int) $id
            : $this->ensureLookup(
                'st_project_statuses',
                'PH1J_ACTIVE',
                'Phase 1J Active'
            );
    }

    protected function priorityId(): int
    {
        $id =
            DB::table(
                'lt_priorities'
            )
            ->where(
                'is_active',
                true
            )
            ->value('id');

        return $id
            ? (int) $id
            : $this->ensureLookup(
                'lt_priorities',
                'PH1J_NORMAL',
                'Phase 1J Normal'
            );
    }

    protected function taskStatusId(): int
    {
        $id =
            DB::table(
                'st_task_statuses'
            )
            ->where(
                'is_active',
                true
            )
            ->value('id');

        return $id
            ? (int) $id
            : $this->ensureLookup(
                'st_task_statuses',
                'PH1J_TODO',
                'Phase 1J To Do'
            );
    }

    protected function riskIssueTypeId(): int
    {
        $id =
            DB::table(
                'lt_risk_issue_types'
            )
            ->where(
                'is_active',
                true
            )
            ->value('id');

        return $id
            ? (int) $id
            : $this->ensureLookup(
                'lt_risk_issue_types',
                'PH1J_RISK',
                'Phase 1J Risk'
            );
    }

    protected function severityId(): int
    {
        $id =
            DB::table(
                'st_severities'
            )
            ->where(
                'is_active',
                true
            )
            ->value('id');

        return $id
            ? (int) $id
            : $this->ensureLookup(
                'st_severities',
                'PH1J_MEDIUM',
                'Phase 1J Medium'
            );
    }

    protected function riskIssueStatusId(): int
    {
        $id =
            DB::table(
                'st_risk_issue_statuses'
            )
            ->where(
                'is_active',
                true
            )
            ->value('id');

        return $id
            ? (int) $id
            : $this->ensureLookup(
                'st_risk_issue_statuses',
                'PH1J_OPEN',
                'Phase 1J Open'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Projects
    |--------------------------------------------------------------------------
    */

    protected function createProject(
        Site $site,
        ?Department $department = null,
        ?User $owner = null
    ): Project {
        $department ??=
            $this->createDepartment(
                $site
            );

        $code =
            $this->token(
                'PRJ'
            );

        return Project::forceCreate([
            'site_id' =>
            (int) $site->id,

            'code' =>
            $code,

            'name' =>
            'Phase 1J Project '
                . $code,

            'description' =>
            'Automated Multi-Site test project.',

            'department_id' =>
            (int) $department->id,

            'owner_user_id' =>
            $owner?->id,

            'project_status_id' =>
            $this->projectStatusId(),

            'priority_id' =>
            $this->priorityId(),

            'currency_code' =>
            'MYR',

            'planned_cost_total' =>
            0,

            'actual_cost_total' =>
            0,

            'committed_cost_total' =>
            0,

            'planned_funding_total' =>
            0,

            'actual_funding_total' =>
            0,

            'planned_progress' =>
            0,

            'progress' =>
            0,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Tasks
    |--------------------------------------------------------------------------
    */

    protected function createTask(
        Project $project
    ): ProjectTask {
        return ProjectTask::forceCreate([
            'project_id' =>
            (int) $project->id,

            'name' =>
            'Phase 1J Task '
                . Str::random(10),

            'task_status_id' =>
            $this->taskStatusId(),

            'progress' =>
            0,

            'duration' =>
            0,

            'sort_order' =>
            0,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Milestones
    |--------------------------------------------------------------------------
    */

    protected function createMilestone(
        Project $project
    ): ProjectMilestone {
        return ProjectMilestone::forceCreate([
            'project_id' =>
            (int) $project->id,

            'name' =>
            'Phase 1J Milestone '
                . Str::random(10),

            'milestone_date' =>
            today()
                ->addDays(7)
                ->toDateString(),

            'status' =>
            'PENDING',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | External Source
    |--------------------------------------------------------------------------
    */

    protected function createExternalSource(
        Site $site
    ): ExternalSource {
        $code =
            $this->token(
                'SRC'
            );

        return ExternalSource::forceCreate([
            'site_id' =>
            (int) $site->id,

            'code' =>
            $code,

            'name' =>
            'Phase 1J Source '
                . $code,

            'base_url' =>
            'https://example.test',

            'is_active' =>
            true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Permit
    |--------------------------------------------------------------------------
    */

    protected function createPermit(
        Site $site,
        ?ExternalSource $source = null
    ): ExternalPermit {
        $source ??=
            $this->createExternalSource(
                $site
            );

        return ExternalPermit::forceCreate([
            'site_id' =>
            (int) $site->id,

            'external_source_id' =>
            (int) $source->id,

            'external_form_id' =>
            $this->token(
                'FORM'
            ),

            'external_permit_id' =>
            $this->token(
                'PERMIT'
            ),

            'normalized_status' =>
            'ACTIVE',

            'is_source_deleted' =>
            false,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Integration Sync Run
    |--------------------------------------------------------------------------
    */

    protected function createSyncRun(
        Site $site,
        ?ExternalSource $source = null,
        ?User $user = null
    ): IntegrationSyncRun {
        $source ??=
            $this->createExternalSource(
                $site
            );

        return IntegrationSyncRun::forceCreate([
            'site_id' =>
            (int) $site->id,

            'external_source_id' =>
            (int) $source->id,

            'integration_code' =>
            'EPTW',

            'sync_type' =>
            'MANUAL',

            'status' =>
            'COMPLETED',

            'started_at' =>
            now()->subMinute(),

            'completed_at' =>
            now(),

            'fetched_count' =>
            0,

            'created_count' =>
            0,

            'updated_count' =>
            0,

            'unchanged_count' =>
            0,

            'deleted_count' =>
            0,

            'failed_count' =>
            0,

            'triggered_by_user_id' =>
            $user?->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Risk / Issue
    |--------------------------------------------------------------------------
    */

    protected function createRiskIssue(
        Site $site,
        ?Project $project = null,
        ?ExternalSource $source = null
    ): ExternalRiskIssue {
        $source ??=
            $this->createExternalSource(
                $site
            );

        return ExternalRiskIssue::forceCreate([
            'site_id' =>
            (int) $site->id,

            'external_source_id' =>
            (int) $source->id,

            'external_id' =>
            $this->token(
                'RISK'
            ),

            'project_id' =>
            $project?->id,

            'type_id' =>
            $this->riskIssueTypeId(),

            'title' =>
            'Phase 1J Risk '
                . Str::random(10),

            'description' =>
            'Automated test risk.',

            'severity_id' =>
            $this->severityId(),

            'risk_issue_status_id' =>
            $this->riskIssueStatusId(),

            'owner' =>
            'Phase 1J',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Files
    |--------------------------------------------------------------------------
    */

    protected function createStoredFile(
        Site $site,
        ?User $uploadedBy = null
    ): StoredFile {
        $path =
            'testing/phase1j/'
            . Str::uuid()
            . '.txt';

        return StoredFile::forceCreate([
            'site_id' =>
            (int) $site->id,

            'disk' =>
            'local',

            'path' =>
            $path,

            'original_name' =>
            'phase1j-'
                . Str::random(8)
                . '.txt',

            'mime_type' =>
            'text/plain',

            'size' =>
            128,

            'checksum' =>
            hash(
                'sha256',
                $path
            ),

            'uploaded_by_user_id' =>
            $uploadedBy?->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Agreement lookup data
    |--------------------------------------------------------------------------
    */

    protected function agreementStatusDraftId(): int
    {
        $id =
            DB::table(
                'st_agreement_statuses'
            )
            ->where(
                'code',
                'DRAFT'
            )
            ->value('id');

        if ($id) {
            return (int) $id;
        }

        return $this->ensureLookup(
            'st_agreement_statuses',
            'DRAFT',
            'Draft',
            [
                'is_terminal' =>
                false,

                'is_system_status' =>
                true,
            ]
        );
    }

    protected function agreementCategoryId(): int
    {
        $id =
            DB::table(
                'lt_agreement_categories'
            )
            ->where(
                'is_active',
                true
            )
            ->value('id');

        if ($id) {
            return (int) $id;
        }

        return $this->ensureLookup(
            'lt_agreement_categories',
            'PH1J_CATEGORY',
            'Phase 1J Category',
            [
                'is_system_category' =>
                false,
            ]
        );
    }

    protected function createCounterparty(): Counterparty
    {
        return Counterparty::forceCreate([
            'counterparty_type' =>
            'COMPANY',

            'legal_name' =>
            'Phase 1J Counterparty '
                . Str::random(10),

            'country' =>
            'Malaysia',

            'is_active' =>
            true,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Agreements
    |--------------------------------------------------------------------------
    */

    protected function createAgreement(
        Site $site,
        Department $department,
        User $owner
    ): Agreement {
        $counterparty =
            $this->createCounterparty();

        $agreement =
            Agreement::forceCreate([
                'site_id' =>
                (int) $site->id,

                'agreement_no' =>
                $this->token(
                    'AGR'
                ),

                'title' =>
                'Phase 1J Agreement '
                    . Str::random(10),

                'department_id' =>
                (int) $department->id,

                'owner_user_id' =>
                (int) $owner->id,

                'counterparty_id' =>
                (int) $counterparty->id,

                'agreement_category_id' =>
                $this->agreementCategoryId(),

                'agreement_status_id' =>
                $this->agreementStatusDraftId(),

                'currency_code' =>
                'MYR',

                'auto_renewal' =>
                false,

                'lifecycle_type' =>
                'ORIGINAL',

                'revision_no' =>
                0,

                'renewal_sequence' =>
                0,

                'is_current_version' =>
                true,

                'created_by_user_id' =>
                (int) $owner->id,

                'updated_by_user_id' =>
                (int) $owner->id,
            ]);

        /*
         * Original agreement is its own root,
         * matching AgreementController::store().
         */
        $agreement->forceFill([
            'root_agreement_id' =>
            (int) $agreement->id,
        ])->save();

        return $agreement->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Audit logs
    |--------------------------------------------------------------------------
    */

    protected function createAuditLog(
        ?Site $site,
        ?User $performedBy = null,
        ?string $entityType = null
    ): int {
        $entityType ??=
            'PH1J_'
            . strtoupper(
                Str::random(8)
            );

        return (int) DB::table(
            'dt_audit_logs'
        )->insertGetId([
            'site_id' =>
            $site?->id,

            'entity_type' =>
            $entityType,

            'entity_id' =>
            1,

            'action' =>
            'TEST',

            'changes' =>
            json_encode([
                'phase' => '1J',
            ]),

            'performed_by_user_id' =>
            $performedBy?->id,

            'source' =>
            'API',

            'performed_at' =>
            now(),

            'created_at' =>
            now(),

            'updated_at' =>
            now(),
        ]);
    }
}
