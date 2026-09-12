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
            throw new RuntimeException('lt_sites does not exist. Apply Phase 1A first.');
        }

        if (!Schema::hasTable('lt_departments') || !Schema::hasColumn('lt_departments', 'site_id')) {
            throw new RuntimeException(
                'lt_departments.site_id does not exist. Apply Phase 1B first.'
            );
        }

        $klangSiteId = DB::table('lt_sites')
            ->where('code', 'KLG')
            ->value('id');

        if (!$klangSiteId) {
            throw new RuntimeException('Default Site KLG was not found.');
        }

        if (!Schema::hasColumn('dt_projects', 'site_id')) {
            Schema::table('dt_projects', function (Blueprint $table) {
                $table->unsignedBigInteger('site_id')
                    ->nullable()
                    ->after('id');
            });
        }

        // Existing projects inherit their current Department's Site first.
        DB::statement(
            'UPDATE dt_projects p
             INNER JOIN lt_departments d
                ON d.id = p.department_id
             SET p.site_id = d.site_id
             WHERE p.site_id IS NULL'
        );

        // Legacy projects without a Department belong to the current KLG Site.
        DB::table('dt_projects')
            ->whereNull('site_id')
            ->update(['site_id' => (int) $klangSiteId]);

        if (DB::table('dt_projects')->whereNull('site_id')->exists()) {
            throw new RuntimeException('Unable to backfill every project site_id.');
        }

        $this->dropSingleColumnUniqueIndex('dt_projects', 'code');

        Schema::table('dt_projects', function (Blueprint $table) {
            $table->foreign('site_id', 'fk_projects_site')
                ->references('id')
                ->on('lt_sites')
                ->restrictOnDelete();

            $table->unique(
                ['site_id', 'code'],
                'uq_projects_site_code'
            );

            $table->index(
                ['site_id', 'project_status_id'],
                'idx_projects_site_status'
            );

            $table->index(
                ['site_id', 'department_id'],
                'idx_projects_site_department'
            );

            $table->index(
                ['site_id', 'owner_user_id'],
                'idx_projects_site_owner'
            );

            $table->index(
                ['site_id', 'updated_at'],
                'idx_projects_site_updated'
            );
        });

        DB::statement(
            'ALTER TABLE dt_projects
             MODIFY site_id BIGINT UNSIGNED NOT NULL'
        );
    }

    public function down(): void
    {
        if (
            DB::table('dt_projects')
            ->select('code')
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->exists()
        ) {
            throw new RuntimeException(
                'Cannot roll back: duplicate project codes now exist across Sites.'
            );
        }

        Schema::table('dt_projects', function (Blueprint $table) {
            $table->dropForeign('fk_projects_site');
            $table->dropUnique('uq_projects_site_code');
            $table->dropIndex('idx_projects_site_status');
            $table->dropIndex('idx_projects_site_department');
            $table->dropIndex('idx_projects_site_owner');
            $table->dropIndex('idx_projects_site_updated');
            $table->dropColumn('site_id');
        });

        Schema::table('dt_projects', function (Blueprint $table) {
            $table->unique('code');
        });
    }

    private function dropSingleColumnUniqueIndex(
        string $table,
        string $column
    ): void {
        $indexes = DB::select(
            'SELECT INDEX_NAME,
                    GROUP_CONCAT(
                        COLUMN_NAME
                        ORDER BY SEQ_IN_INDEX
                        SEPARATOR ","
                    ) AS columns_list
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND NON_UNIQUE = 0
               AND INDEX_NAME <> "PRIMARY"
             GROUP BY INDEX_NAME',
            [DB::getDatabaseName(), $table]
        );

        foreach ($indexes as $index) {
            if ($index->columns_list !== $column) {
                continue;
            }

            $indexName = str_replace('`', '``', $index->INDEX_NAME);

            DB::statement(
                "ALTER TABLE `{$table}` DROP INDEX `{$indexName}`"
            );
        }
    }
};
