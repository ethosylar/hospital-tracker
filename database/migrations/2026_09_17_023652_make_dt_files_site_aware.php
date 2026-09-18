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
        if (!Schema::hasTable('lt_sites')) {
            throw new RuntimeException(
                'lt_sites does not exist. Apply the Multi-Site foundation first.'
            );
        }

        if (
            !Schema::hasTable('dt_projects')
            || !Schema::hasColumn('dt_projects', 'site_id')
        ) {
            throw new RuntimeException(
                'dt_projects.site_id does not exist. Apply Phase 1C first.'
            );
        }

        if (
            !Schema::hasTable('dt_agreements')
            || !Schema::hasColumn('dt_agreements', 'site_id')
        ) {
            throw new RuntimeException(
                'dt_agreements.site_id does not exist. Apply Phase 1D first.'
            );
        }

        $klangSiteId = DB::table('lt_sites')
            ->where('code', 'KLG')
            ->value('id');

        if (!$klangSiteId) {
            throw new RuntimeException(
                'Default Site KLG was not found.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Add site_id
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn('dt_files', 'site_id')) {
            Schema::table(
                'dt_files',
                function (Blueprint $table) {
                    $table->unsignedBigInteger('site_id')
                        ->nullable()
                        ->after('id');
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Backfill #1 - Project files
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('dt_project_files')) {
            DB::statement(
                '
                UPDATE dt_files f
                INNER JOIN dt_project_files pf
                    ON pf.file_id = f.id
                INNER JOIN dt_projects p
                    ON p.id = pf.project_id
                SET f.site_id = p.site_id
                WHERE f.site_id IS NULL
                '
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Backfill #2 - Task files
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('dt_task_files')
            && Schema::hasTable('dt_project_tasks')
        ) {
            DB::statement(
                '
                UPDATE dt_files f
                INNER JOIN dt_task_files tf
                    ON tf.file_id = f.id
                INNER JOIN dt_project_tasks t
                    ON t.id = tf.task_id
                INNER JOIN dt_projects p
                    ON p.id = t.project_id
                SET f.site_id = p.site_id
                WHERE f.site_id IS NULL
                '
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Backfill #3 - Agreement files
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('dt_agreement_files')) {
            DB::statement(
                '
                UPDATE dt_files f
                INNER JOIN dt_agreement_files af
                    ON af.file_id = f.id
                INNER JOIN dt_agreements a
                    ON a.id = af.agreement_id
                SET f.site_id = a.site_id
                WHERE f.site_id IS NULL
                '
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Legacy orphan fallback
        |--------------------------------------------------------------------------
        |
        | Files with no current Project/Task/Agreement link belong to the
        | original Klang deployment.
        |--------------------------------------------------------------------------
        */

        DB::table('dt_files')
            ->whereNull('site_id')
            ->update([
                'site_id' => (int) $klangSiteId,
            ]);

        if (
            DB::table('dt_files')
            ->whereNull('site_id')
            ->exists()
        ) {
            throw new RuntimeException(
                'Unable to backfill every dt_files.site_id.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Project file links
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('dt_project_files')) {
            $projectMismatch = DB::table(
                'dt_project_files as pf'
            )
                ->join(
                    'dt_files as f',
                    'f.id',
                    '=',
                    'pf.file_id'
                )
                ->join(
                    'dt_projects as p',
                    'p.id',
                    '=',
                    'pf.project_id'
                )
                ->whereColumn(
                    'f.site_id',
                    '<>',
                    'p.site_id'
                )
                ->count();

            if ($projectMismatch > 0) {
                throw new RuntimeException(
                    "Found {$projectMismatch} Project file link(s) crossing Sites."
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Task file links
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasTable('dt_task_files')
            && Schema::hasTable('dt_project_tasks')
        ) {
            $taskMismatch = DB::table(
                'dt_task_files as tf'
            )
                ->join(
                    'dt_files as f',
                    'f.id',
                    '=',
                    'tf.file_id'
                )
                ->join(
                    'dt_project_tasks as t',
                    't.id',
                    '=',
                    'tf.task_id'
                )
                ->join(
                    'dt_projects as p',
                    'p.id',
                    '=',
                    't.project_id'
                )
                ->whereColumn(
                    'f.site_id',
                    '<>',
                    'p.site_id'
                )
                ->count();

            if ($taskMismatch > 0) {
                throw new RuntimeException(
                    "Found {$taskMismatch} Task file link(s) crossing Sites."
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Agreement file links
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('dt_agreement_files')) {
            $agreementMismatch = DB::table(
                'dt_agreement_files as af'
            )
                ->join(
                    'dt_files as f',
                    'f.id',
                    '=',
                    'af.file_id'
                )
                ->join(
                    'dt_agreements as a',
                    'a.id',
                    '=',
                    'af.agreement_id'
                )
                ->whereColumn(
                    'f.site_id',
                    '<>',
                    'a.site_id'
                )
                ->count();

            if ($agreementMismatch > 0) {
                throw new RuntimeException(
                    "Found {$agreementMismatch} Agreement file link(s) crossing Sites."
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FK / indexes
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'dt_files',
            function (Blueprint $table) {
                $table->foreign(
                    'site_id',
                    'fk_files_site'
                )
                    ->references('id')
                    ->on('lt_sites')
                    ->restrictOnDelete();

                $table->index(
                    [
                        'site_id',
                        'uploaded_by_user_id',
                    ],
                    'idx_files_site_uploader'
                );

                $table->index(
                    [
                        'site_id',
                        'checksum',
                    ],
                    'idx_files_site_checksum'
                );

                $table->index(
                    [
                        'site_id',
                        'created_at',
                    ],
                    'idx_files_site_created'
                );
            }
        );

        DB::statement(
            '
            ALTER TABLE dt_files
            MODIFY site_id BIGINT UNSIGNED NOT NULL
            '
        );
    }

    public function down(): void
    {
        Schema::table(
            'dt_files',
            function (Blueprint $table) {
                $table->dropIndex(
                    'idx_files_site_uploader'
                );

                $table->dropIndex(
                    'idx_files_site_checksum'
                );

                $table->dropIndex(
                    'idx_files_site_created'
                );

                $table->dropForeign(
                    'fk_files_site'
                );

                $table->dropColumn(
                    'site_id'
                );
            }
        );
    }
};
