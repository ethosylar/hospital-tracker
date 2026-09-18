<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Prerequisite
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('lt_sites')) {
            throw new RuntimeException(
                'lt_sites does not exist. Apply the Multi-Site foundation first.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Add nullable Site
        |--------------------------------------------------------------------------
        |
        | NULL is intentional.
        |
        | Global audit events such as USER, ROLE, PERMISSION and AUTH
        | are not owned by one Site.
        |--------------------------------------------------------------------------
        */

        if (
            !Schema::hasColumn(
                'dt_audit_logs',
                'site_id'
            )
        ) {
            Schema::table(
                'dt_audit_logs',
                function (Blueprint $table) {
                    $table->unsignedBigInteger('site_id')
                        ->nullable()
                        ->after('id');
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Backfill from audit payload
        |--------------------------------------------------------------------------
        |
        | Newer Phase 1C-1F audit payloads already contain site_id in
        | several places.
        |--------------------------------------------------------------------------
        */

        DB::statement(
            "
            UPDATE dt_audit_logs a
            INNER JOIN lt_sites s
                ON s.id = CAST(
                    JSON_UNQUOTE(
                        JSON_EXTRACT(
                            a.changes,
                            '$.site_id'
                        )
                    )
                    AS UNSIGNED
                )
            SET a.site_id = s.id
            WHERE a.site_id IS NULL
              AND a.changes IS NOT NULL
              AND JSON_EXTRACT(
                    a.changes,
                    '$.site_id'
                  ) IS NOT NULL
            "
        );

        /*
         * DELETE audit records often keep values inside snapshot.
         */
        DB::statement(
            "
            UPDATE dt_audit_logs a
            INNER JOIN lt_sites s
                ON s.id = CAST(
                    JSON_UNQUOTE(
                        JSON_EXTRACT(
                            a.changes,
                            '$.snapshot.site_id'
                        )
                    )
                    AS UNSIGNED
                )
            SET a.site_id = s.id
            WHERE a.site_id IS NULL
              AND a.changes IS NOT NULL
              AND JSON_EXTRACT(
                    a.changes,
                    '$.snapshot.site_id'
                  ) IS NOT NULL
            "
        );

        /*
        |--------------------------------------------------------------------------
        | Project
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('dt_projects')) {
            DB::statement(
                "
                UPDATE dt_audit_logs a
                INNER JOIN dt_projects p
                    ON p.id = a.entity_id
                SET a.site_id = p.site_id
                WHERE a.site_id IS NULL
                  AND a.entity_type = 'PROJECT'
                "
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Task
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('dt_project_tasks')
            && Schema::hasTable('dt_projects')
        ) {
            DB::statement(
                "
                UPDATE dt_audit_logs a
                INNER JOIN dt_project_tasks t
                    ON t.id = a.entity_id
                INNER JOIN dt_projects p
                    ON p.id = t.project_id
                SET a.site_id = p.site_id
                WHERE a.site_id IS NULL
                  AND a.entity_type = 'TASK'
                "
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Project Milestone
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('dt_project_milestones')
            && Schema::hasTable('dt_projects')
        ) {
            DB::statement(
                "
                UPDATE dt_audit_logs a
                INNER JOIN dt_project_milestones m
                    ON m.id = a.entity_id
                INNER JOIN dt_projects p
                    ON p.id = m.project_id
                SET a.site_id = p.site_id
                WHERE a.site_id IS NULL
                  AND a.entity_type = 'PROJECT_MILESTONE'
                "
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Budget Line
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('dt_project_budget_lines')
            && Schema::hasTable('dt_projects')
        ) {
            DB::statement(
                "
                UPDATE dt_audit_logs a
                INNER JOIN dt_project_budget_lines b
                    ON b.id = a.entity_id
                INNER JOIN dt_projects p
                    ON p.id = b.project_id
                SET a.site_id = p.site_id
                WHERE a.site_id IS NULL
                  AND a.entity_type IN (
                      'PROJECT_BUDGET',
                      'PROJECT_BUDGET_LINE'
                  )
                "
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Budget Allocation
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('dt_project_budget_allocations')
            && Schema::hasTable('dt_projects')
        ) {
            DB::statement(
                "
                UPDATE dt_audit_logs a
                INNER JOIN dt_project_budget_allocations ba
                    ON ba.id = a.entity_id
                INNER JOIN dt_projects p
                    ON p.id = ba.project_id
                SET a.site_id = p.site_id
                WHERE a.site_id IS NULL
                  AND a.entity_type = 'PROJECT_BUDGET_ALLOCATION'
                "
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Department
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('lt_departments')
            && Schema::hasColumn(
                'lt_departments',
                'site_id'
            )
        ) {
            DB::statement(
                "
                UPDATE dt_audit_logs a
                INNER JOIN lt_departments d
                    ON d.id = a.entity_id
                SET a.site_id = d.site_id
                WHERE a.site_id IS NULL
                  AND a.entity_type = 'DEPARTMENT'
                "
            );
        }

        /*
        |--------------------------------------------------------------------------
        | External Source
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('lt_external_sources')
            && Schema::hasColumn(
                'lt_external_sources',
                'site_id'
            )
        ) {
            DB::statement(
                "
                UPDATE dt_audit_logs a
                INNER JOIN lt_external_sources src
                    ON src.id = a.entity_id
                SET a.site_id = src.site_id
                WHERE a.site_id IS NULL
                  AND a.entity_type = 'EXTERNAL_SOURCE'
                "
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ePTW Permit
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('dt_external_permits')
            && Schema::hasColumn(
                'dt_external_permits',
                'site_id'
            )
        ) {
            DB::statement(
                "
                UPDATE dt_audit_logs a
                INNER JOIN dt_external_permits permit
                    ON permit.id = a.entity_id
                SET a.site_id = permit.site_id
                WHERE a.site_id IS NULL
                  AND a.entity_type = 'EXTERNAL_PERMIT'
                "
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ePTW Sync Run
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('dt_integration_sync_runs')
            && Schema::hasColumn(
                'dt_integration_sync_runs',
                'site_id'
            )
        ) {
            DB::statement(
                "
                UPDATE dt_audit_logs a
                INNER JOIN dt_integration_sync_runs r
                    ON r.id = a.entity_id
                SET a.site_id = r.site_id
                WHERE a.site_id IS NULL
                  AND a.entity_type = 'EPTW_SYNC'
                "
            );
        }

        /*
        |--------------------------------------------------------------------------
        | External Risk / Issue
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('dt_external_risk_issues')
            && Schema::hasColumn(
                'dt_external_risk_issues',
                'site_id'
            )
        ) {
            DB::statement(
                "
                UPDATE dt_audit_logs a
                INNER JOIN dt_external_risk_issues ri
                    ON ri.id = a.entity_id
                SET a.site_id = ri.site_id
                WHERE a.site_id IS NULL
                  AND a.entity_type = 'EXTERNAL_RISK_ISSUE'
                "
            );
        }

        /*
        |--------------------------------------------------------------------------
        | External Risk / Issue Link
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable(
                'dt_external_risk_issue_links'
            )
            && Schema::hasTable(
                'dt_external_risk_issues'
            )
        ) {
            DB::statement(
                "
                UPDATE dt_audit_logs a
                INNER JOIN dt_external_risk_issue_links l
                    ON l.id = a.entity_id
                INNER JOIN dt_external_risk_issues ri
                    ON ri.id = l.external_risk_issue_id
                SET a.site_id = ri.site_id
                WHERE a.site_id IS NULL
                  AND a.entity_type = 'EXTERNAL_RISK_ISSUE_LINK'
                "
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Agreement
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('dt_agreements')
            && Schema::hasColumn(
                'dt_agreements',
                'site_id'
            )
        ) {
            DB::statement(
                "
                UPDATE dt_audit_logs a
                INNER JOIN dt_agreements agreement
                    ON agreement.id = a.entity_id
                SET a.site_id = agreement.site_id
                WHERE a.site_id IS NULL
                  AND a.entity_type = 'AGREEMENT'
                "
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Agreement File
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('dt_agreement_files')
            && Schema::hasTable('dt_agreements')
        ) {
            DB::statement(
                "
                UPDATE dt_audit_logs a
                INNER JOIN dt_agreement_files af
                    ON af.id = a.entity_id
                INNER JOIN dt_agreements agreement
                    ON agreement.id = af.agreement_id
                SET a.site_id = agreement.site_id
                WHERE a.site_id IS NULL
                  AND a.entity_type = 'AGREEMENT_FILE'
                "
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Agreement Project Link
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable(
                'dt_agreement_project_links'
            )
            && Schema::hasTable(
                'dt_agreements'
            )
        ) {
            DB::statement(
                "
                UPDATE dt_audit_logs a
                INNER JOIN dt_agreement_project_links apl
                    ON apl.id = a.entity_id
                INNER JOIN dt_agreements agreement
                    ON agreement.id = apl.agreement_id
                SET a.site_id = agreement.site_id
                WHERE a.site_id IS NULL
                  AND a.entity_type = 'AGREEMENT_PROJECT_LINK'
                "
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Project Permit Link
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('dt_project_permit_links')
            && Schema::hasTable('dt_projects')
        ) {
            DB::statement(
                "
                UPDATE dt_audit_logs a
                INNER JOIN dt_project_permit_links ppl
                    ON ppl.id = a.entity_id
                INNER JOIN dt_projects p
                    ON p.id = ppl.project_id
                SET a.site_id = p.site_id
                WHERE a.site_id IS NULL
                  AND a.entity_type = 'PROJECT_PERMIT_LINK'
                "
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Recover Site through project_id embedded in audit payload
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('dt_projects')) {
            DB::statement(
                "
                UPDATE dt_audit_logs a
                INNER JOIN dt_projects p
                    ON p.id = CAST(
                        JSON_UNQUOTE(
                            JSON_EXTRACT(
                                a.changes,
                                '$.project_id'
                            )
                        )
                        AS UNSIGNED
                    )
                SET a.site_id = p.site_id
                WHERE a.site_id IS NULL
                  AND a.changes IS NOT NULL
                  AND JSON_EXTRACT(
                        a.changes,
                        '$.project_id'
                      ) IS NOT NULL
                "
            );

            DB::statement(
                "
                UPDATE dt_audit_logs a
                INNER JOIN dt_projects p
                    ON p.id = CAST(
                        JSON_UNQUOTE(
                            JSON_EXTRACT(
                                a.changes,
                                '$.snapshot.project_id'
                            )
                        )
                        AS UNSIGNED
                    )
                SET a.site_id = p.site_id
                WHERE a.site_id IS NULL
                  AND a.changes IS NOT NULL
                  AND JSON_EXTRACT(
                        a.changes,
                        '$.snapshot.project_id'
                      ) IS NOT NULL
                "
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Agreement ID embedded in audit payload
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('dt_agreements')) {
            DB::statement(
                "
                UPDATE dt_audit_logs a
                INNER JOIN dt_agreements agreement
                    ON agreement.id = CAST(
                        JSON_UNQUOTE(
                            JSON_EXTRACT(
                                a.changes,
                                '$.agreement_id'
                            )
                        )
                        AS UNSIGNED
                    )
                SET a.site_id = agreement.site_id
                WHERE a.site_id IS NULL
                  AND a.changes IS NOT NULL
                  AND JSON_EXTRACT(
                        a.changes,
                        '$.agreement_id'
                      ) IS NOT NULL
                "
            );
        }

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | We intentionally DO NOT assign all remaining NULL logs to KLG.
        |
        | Remaining NULL rows are either:
        |
        | - global system/admin audit events
        | - old Site-owned records which cannot be resolved safely
        |
        | Assigning them blindly to KLG could expose records incorrectly.
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'dt_audit_logs',
            function (Blueprint $table) {
                $table->foreign(
                    'site_id',
                    'fk_audit_logs_site'
                )
                    ->references('id')
                    ->on('lt_sites')
                    ->restrictOnDelete();

                $table->index(
                    [
                        'site_id',
                        'performed_at',
                    ],
                    'idx_audit_logs_site_time'
                );

                $table->index(
                    [
                        'site_id',
                        'entity_type',
                        'entity_id',
                    ],
                    'idx_audit_logs_site_entity'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'dt_audit_logs',
            function (Blueprint $table) {
                $table->dropIndex(
                    'idx_audit_logs_site_time'
                );

                $table->dropIndex(
                    'idx_audit_logs_site_entity'
                );

                $table->dropForeign(
                    'fk_audit_logs_site'
                );

                $table->dropColumn(
                    'site_id'
                );
            }
        );
    }
};
