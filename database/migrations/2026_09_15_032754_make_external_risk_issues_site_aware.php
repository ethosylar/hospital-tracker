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
        | Prerequisites
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('lt_sites')) {
            throw new RuntimeException(
                'lt_sites does not exist. Apply Phase 1A first.'
            );
        }

        if (!Schema::hasColumn('dt_projects', 'site_id')) {
            throw new RuntimeException(
                'dt_projects.site_id does not exist. Apply Phase 1C first.'
            );
        }

        if (!Schema::hasColumn('lt_external_sources', 'site_id')) {
            throw new RuntimeException(
                'lt_external_sources.site_id does not exist. Apply Phase 1E first.'
            );
        }

        if (!Schema::hasColumn('dt_external_permits', 'site_id')) {
            throw new RuntimeException(
                'dt_external_permits.site_id does not exist. Apply Phase 1E first.'
            );
        }

        $klangSiteId = DB::table('lt_sites')
            ->where('code', 'KLG')
            ->value('id');

        if (!$klangSiteId) {
            throw new RuntimeException(
                'Default KLG Site was not found.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Add site_id
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn('dt_external_risk_issues', 'site_id')) {
            Schema::table(
                'dt_external_risk_issues',
                function (Blueprint $table) {
                    $table->unsignedBigInteger('site_id')
                        ->nullable()
                        ->after('id');
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Backfill #1 - External Source
        |--------------------------------------------------------------------------
        */

        DB::statement(
            '
            UPDATE dt_external_risk_issues ri
            INNER JOIN lt_external_sources src
                ON src.id = ri.external_source_id
            SET ri.site_id = src.site_id
            WHERE ri.site_id IS NULL
              AND ri.external_source_id IS NOT NULL
            '
        );

        /*
        |--------------------------------------------------------------------------
        | Backfill #2 - Direct Project
        |--------------------------------------------------------------------------
        */

        DB::statement(
            '
            UPDATE dt_external_risk_issues ri
            INNER JOIN dt_projects p
                ON p.id = ri.project_id
            SET ri.site_id = p.site_id
            WHERE ri.site_id IS NULL
              AND ri.project_id IS NOT NULL
            '
        );

        /*
        |--------------------------------------------------------------------------
        | Backfill #3 - Linked Project
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('dt_external_risk_issue_links')) {
            DB::statement(
                '
                UPDATE dt_external_risk_issues ri
                INNER JOIN dt_external_risk_issue_links l
                    ON l.external_risk_issue_id = ri.id
                INNER JOIN dt_projects p
                    ON p.id = l.project_id
                SET ri.site_id = p.site_id
                WHERE ri.site_id IS NULL
                  AND l.project_id IS NOT NULL
                '
            );

            /*
            |--------------------------------------------------------------------------
            | Backfill #4 - Linked Task -> Project
            |--------------------------------------------------------------------------
            */

            DB::statement(
                '
                UPDATE dt_external_risk_issues ri
                INNER JOIN dt_external_risk_issue_links l
                    ON l.external_risk_issue_id = ri.id
                INNER JOIN dt_project_tasks t
                    ON t.id = l.task_id
                INNER JOIN dt_projects p
                    ON p.id = t.project_id
                SET ri.site_id = p.site_id
                WHERE ri.site_id IS NULL
                  AND l.task_id IS NOT NULL
                '
            );

            /*
            |--------------------------------------------------------------------------
            | Backfill #5 - Linked Milestone -> Project
            |--------------------------------------------------------------------------
            */

            DB::statement(
                '
                UPDATE dt_external_risk_issues ri
                INNER JOIN dt_external_risk_issue_links l
                    ON l.external_risk_issue_id = ri.id
                INNER JOIN dt_project_milestones m
                    ON m.id = l.milestone_id
                INNER JOIN dt_projects p
                    ON p.id = m.project_id
                SET ri.site_id = p.site_id
                WHERE ri.site_id IS NULL
                  AND l.milestone_id IS NOT NULL
                '
            );

            /*
            |--------------------------------------------------------------------------
            | Backfill #6 - Linked ePTW Permit
            |--------------------------------------------------------------------------
            */

            DB::statement(
                '
                UPDATE dt_external_risk_issues ri
                INNER JOIN dt_external_risk_issue_links l
                    ON l.external_risk_issue_id = ri.id
                INNER JOIN dt_external_permits permit
                    ON permit.id = l.permit_id
                SET ri.site_id = permit.site_id
                WHERE ri.site_id IS NULL
                  AND l.permit_id IS NOT NULL
                '
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Legacy fallback
        |--------------------------------------------------------------------------
        |
        | Only records with no source/project/link ownership clue reach here.
        |--------------------------------------------------------------------------
        */

        DB::table('dt_external_risk_issues')
            ->whereNull('site_id')
            ->update([
                'site_id' => (int) $klangSiteId,
            ]);

        /*
        |--------------------------------------------------------------------------
        | Validate Source Site
        |--------------------------------------------------------------------------
        */

        $sourceMismatch = DB::table('dt_external_risk_issues as ri')
            ->join('lt_external_sources as src', 'src.id', '=', 'ri.external_source_id')
            ->whereColumn('ri.site_id', '<>', 'src.site_id')
            ->count();

        if ($sourceMismatch > 0) {
            throw new RuntimeException(
                "Found {$sourceMismatch} External Risk/Issue record(s) "
                    . 'whose External Source belongs to another Site.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate direct Project Site
        |--------------------------------------------------------------------------
        */

        $projectMismatch = DB::table('dt_external_risk_issues as ri')
            ->join('dt_projects as p', 'p.id', '=', 'ri.project_id')
            ->whereColumn('ri.site_id', '<>', 'p.site_id')
            ->count();

        if ($projectMismatch > 0) {
            throw new RuntimeException(
                "Found {$projectMismatch} External Risk/Issue record(s) "
                    . 'whose Project belongs to another Site.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate links
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('dt_external_risk_issue_links')) {
            $linkedProjectMismatch = DB::table('dt_external_risk_issue_links as l')
                ->join('dt_external_risk_issues as ri', 'ri.id', '=', 'l.external_risk_issue_id')
                ->join('dt_projects as p', 'p.id', '=', 'l.project_id')
                ->whereNotNull('l.project_id')
                ->whereColumn('ri.site_id', '<>', 'p.site_id')
                ->count();

            if ($linkedProjectMismatch > 0) {
                throw new RuntimeException(
                    "Found {$linkedProjectMismatch} Risk/Issue "
                        . 'Project link(s) crossing Sites.'
                );
            }

            $linkedTaskMismatch = DB::table('dt_external_risk_issue_links as l')
                ->join('dt_external_risk_issues as ri', 'ri.id', '=', 'l.external_risk_issue_id')
                ->join('dt_project_tasks as t', 't.id', '=', 'l.task_id')
                ->join('dt_projects as p', 'p.id', '=', 't.project_id')
                ->whereNotNull('l.task_id')
                ->whereColumn('ri.site_id', '<>', 'p.site_id')
                ->count();

            if ($linkedTaskMismatch > 0) {
                throw new RuntimeException(
                    "Found {$linkedTaskMismatch} Risk/Issue "
                        . 'Task link(s) crossing Sites.'
                );
            }

            $linkedMilestoneMismatch = DB::table('dt_external_risk_issue_links as l')
                ->join('dt_external_risk_issues as ri', 'ri.id', '=', 'l.external_risk_issue_id')
                ->join('dt_project_milestones as m', 'm.id', '=', 'l.milestone_id')
                ->join('dt_projects as p', 'p.id', '=', 'm.project_id')
                ->whereNotNull('l.milestone_id')
                ->whereColumn('ri.site_id', '<>', 'p.site_id')
                ->count();

            if ($linkedMilestoneMismatch > 0) {
                throw new RuntimeException(
                    "Found {$linkedMilestoneMismatch} Risk/Issue "
                        . 'Milestone link(s) crossing Sites.'
                );
            }

            $linkedPermitMismatch = DB::table('dt_external_risk_issue_links as l')
                ->join('dt_external_risk_issues as ri', 'ri.id', '=', 'l.external_risk_issue_id')
                ->join('dt_external_permits as permit', 'permit.id', '=', 'l.permit_id')
                ->whereNotNull('l.permit_id')
                ->whereColumn('ri.site_id', '<>', 'permit.site_id')
                ->count();

            if ($linkedPermitMismatch > 0) {
                throw new RuntimeException(
                    "Found {$linkedPermitMismatch} Risk/Issue "
                        . 'Permit link(s) crossing Sites.'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FK and indexes
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'dt_external_risk_issues',
            function (Blueprint $table) {
                $table->foreign('site_id', 'fk_external_risk_issues_site')
                    ->references('id')
                    ->on('lt_sites')
                    ->restrictOnDelete();
                $table->index(['site_id', 'risk_issue_status_id',], 'idx_external_ri_site_status');
                $table->index(['site_id', 'severity_id',], 'idx_external_ri_site_severity');
                $table->index(['site_id', 'type_id',], 'idx_external_ri_site_type');
                $table->index(['site_id', 'project_id',], 'idx_external_ri_site_project');
                $table->index(['site_id', 'source_updated_at',], 'idx_external_ri_site_updated');
            }
        );

        DB::statement(
            '
            ALTER TABLE dt_external_risk_issues
            MODIFY site_id BIGINT UNSIGNED NOT NULL
            '
        );
    }

    public function down(): void
    {
        Schema::table(
            'dt_external_risk_issues',
            function (Blueprint $table) {
                $table->dropIndex('idx_external_ri_site_status');
                $table->dropIndex('idx_external_ri_site_severity');
                $table->dropIndex('idx_external_ri_site_type');
                $table->dropIndex('idx_external_ri_site_project');
                $table->dropIndex('idx_external_ri_site_updated');
                $table->dropForeign('fk_external_risk_issues_site');
                $table->dropColumn('site_id');
            }
        );
    }
};
