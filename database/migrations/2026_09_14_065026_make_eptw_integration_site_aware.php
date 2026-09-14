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
                'lt_sites does not exist. Apply Phase 1A first.'
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
        | 1. External Sources
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn(
            'lt_external_sources',
            'site_id'
        )) {
            Schema::table(
                'lt_external_sources',
                function (Blueprint $table) {
                    $table->unsignedBigInteger('site_id')
                        ->nullable()
                        ->after('id');
                }
            );
        }

        /*
         * Existing HPMS data originated from the original Klang deployment.
         */
        DB::table('lt_external_sources')
            ->whereNull('site_id')
            ->update([
                'site_id' => (int) $klangSiteId,
            ]);

        /*
        |--------------------------------------------------------------------------
        | 2. External Permits
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn(
            'dt_external_permits',
            'site_id'
        )) {
            Schema::table(
                'dt_external_permits',
                function (Blueprint $table) {
                    $table->unsignedBigInteger('site_id')
                        ->nullable()
                        ->after('id');
                }
            );
        }

        /*
         * Permit inherits Site from its External Source.
         */
        DB::statement(
            '
            UPDATE dt_external_permits p
            INNER JOIN lt_external_sources s
                ON s.id = p.external_source_id
            SET p.site_id = s.site_id
            WHERE p.site_id IS NULL
            '
        );

        /*
        |--------------------------------------------------------------------------
        | 3. Integration Sync Runs
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn(
            'dt_integration_sync_runs',
            'site_id'
        )) {
            Schema::table(
                'dt_integration_sync_runs',
                function (Blueprint $table) {
                    $table->unsignedBigInteger('site_id')
                        ->nullable()
                        ->after('id');
                }
            );
        }

        DB::statement(
            '
            UPDATE dt_integration_sync_runs r
            INNER JOIN lt_external_sources s
                ON s.id = r.external_source_id
            SET r.site_id = s.site_id
            WHERE r.site_id IS NULL
            '
        );

        /*
        |--------------------------------------------------------------------------
        | Validate backfill
        |--------------------------------------------------------------------------
        */

        if (
            DB::table('lt_external_sources')
            ->whereNull('site_id')
            ->exists()
        ) {
            throw new RuntimeException(
                'Some External Sources do not have a Site.'
            );
        }

        if (
            DB::table('dt_external_permits')
            ->whereNull('site_id')
            ->exists()
        ) {
            throw new RuntimeException(
                'Some External Permits do not have a Site.'
            );
        }

        if (
            DB::table('dt_integration_sync_runs')
            ->whereNull('site_id')
            ->exists()
        ) {
            throw new RuntimeException(
                'Some Integration Sync Runs do not have a Site.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate existing Permit <-> Project links
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('dt_project_permit_links')) {
            $crossSiteCount = DB::table(
                'dt_project_permit_links as link'
            )
                ->join(
                    'dt_external_permits as permit',
                    'permit.id',
                    '=',
                    'link.permit_id'
                )
                ->join(
                    'dt_projects as project',
                    'project.id',
                    '=',
                    'link.project_id'
                )
                ->whereColumn(
                    'permit.site_id',
                    '<>',
                    'project.site_id'
                )
                ->count();

            if ($crossSiteCount > 0) {
                throw new RuntimeException(
                    "Found {$crossSiteCount} Permit/Project "
                        . 'cross-Site link(s). Correct them before '
                        . 'applying Phase 1E.'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | External Source code uniqueness
        |--------------------------------------------------------------------------
        |
        | Before:
        |
        | EPTW globally unique
        |
        | After:
        |
        | KLG / EPTW
        | AMP / EPTW
        |--------------------------------------------------------------------------
        */

        $this->dropSingleColumnUniqueIndex(
            'lt_external_sources',
            'code'
        );

        /*
        |--------------------------------------------------------------------------
        | Add constraints
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'lt_external_sources',
            function (Blueprint $table) {
                $table->foreign(
                    'site_id',
                    'fk_external_sources_site'
                )
                    ->references('id')
                    ->on('lt_sites')
                    ->restrictOnDelete();

                $table->unique(
                    ['site_id', 'code'],
                    'uq_external_sources_site_code'
                );

                $table->index(
                    ['site_id', 'is_active'],
                    'idx_external_sources_site_active'
                );
            }
        );

        Schema::table(
            'dt_external_permits',
            function (Blueprint $table) {
                $table->foreign(
                    'site_id',
                    'fk_external_permits_site'
                )
                    ->references('id')
                    ->on('lt_sites')
                    ->restrictOnDelete();

                $table->index(
                    ['site_id', 'normalized_status'],
                    'idx_external_permits_site_status'
                );

                $table->index(
                    [
                        'site_id',
                        'work_start_date',
                        'work_end_date',
                    ],
                    'idx_external_permits_site_dates'
                );
            }
        );

        Schema::table(
            'dt_integration_sync_runs',
            function (Blueprint $table) {
                $table->foreign(
                    'site_id',
                    'fk_sync_runs_site'
                )
                    ->references('id')
                    ->on('lt_sites')
                    ->restrictOnDelete();

                $table->index(
                    [
                        'site_id',
                        'integration_code',
                        'started_at',
                    ],
                    'idx_sync_runs_site_integration'
                );

                $table->index(
                    ['site_id', 'status'],
                    'idx_sync_runs_site_status'
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Make Site mandatory
        |--------------------------------------------------------------------------
        */

        DB::statement(
            '
            ALTER TABLE lt_external_sources
            MODIFY site_id BIGINT UNSIGNED NOT NULL
            '
        );

        DB::statement(
            '
            ALTER TABLE dt_external_permits
            MODIFY site_id BIGINT UNSIGNED NOT NULL
            '
        );

        DB::statement(
            '
            ALTER TABLE dt_integration_sync_runs
            MODIFY site_id BIGINT UNSIGNED NOT NULL
            '
        );
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Can global External Source code uniqueness be restored?
        |--------------------------------------------------------------------------
        */

        $duplicateCode = DB::table(
            'lt_external_sources'
        )
            ->select(
                'code',
                DB::raw('COUNT(*) AS total')
            )
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicateCode) {
            throw new RuntimeException(
                'Cannot roll back Site-aware External Sources because '
                    . "code '{$duplicateCode->code}' exists in more than "
                    . 'one Site.'
            );
        }

        Schema::table(
            'dt_integration_sync_runs',
            function (Blueprint $table) {
                $table->dropIndex(
                    'idx_sync_runs_site_integration'
                );

                $table->dropIndex(
                    'idx_sync_runs_site_status'
                );

                $table->dropForeign(
                    'fk_sync_runs_site'
                );

                $table->dropColumn(
                    'site_id'
                );
            }
        );

        Schema::table(
            'dt_external_permits',
            function (Blueprint $table) {
                $table->dropIndex(
                    'idx_external_permits_site_status'
                );

                $table->dropIndex(
                    'idx_external_permits_site_dates'
                );

                $table->dropForeign(
                    'fk_external_permits_site'
                );

                $table->dropColumn(
                    'site_id'
                );
            }
        );

        Schema::table(
            'lt_external_sources',
            function (Blueprint $table) {
                $table->dropUnique(
                    'uq_external_sources_site_code'
                );

                $table->dropIndex(
                    'idx_external_sources_site_active'
                );

                $table->dropForeign(
                    'fk_external_sources_site'
                );

                $table->dropColumn(
                    'site_id'
                );
            }
        );

        Schema::table(
            'lt_external_sources',
            function (Blueprint $table) {
                $table->unique(
                    'code',
                    'lt_external_sources_code_unique'
                );
            }
        );
    }

    private function dropSingleColumnUniqueIndex(
        string $table,
        string $column
    ): void {
        $database = DB::getDatabaseName();

        $indexes = DB::select(
            '
            SELECT
                INDEX_NAME,
                COUNT(*) AS column_count,
                MAX(COLUMN_NAME = ?) AS contains_target
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND NON_UNIQUE = 0
              AND INDEX_NAME <> "PRIMARY"
            GROUP BY INDEX_NAME
            HAVING column_count = 1
               AND contains_target = 1
            ',
            [
                $column,
                $database,
                $table,
            ]
        );

        foreach ($indexes as $index) {
            $indexName = str_replace(
                '`',
                '``',
                $index->INDEX_NAME
            );

            DB::statement(
                "ALTER TABLE `{$table}` "
                    . "DROP INDEX `{$indexName}`"
            );
        }
    }
};
