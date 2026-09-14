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
        | Prerequisite checks
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasTable('lt_sites')) {
            throw new RuntimeException(
                'lt_sites does not exist. Apply Phase 1A first.'
            );
        }

        if (!Schema::hasTable('lt_departments') || !Schema::hasColumn('lt_departments', 'site_id')) {
            throw new RuntimeException(
                'lt_departments.site_id does not exist. Apply Phase 1B first.'
            );
        }

        if (!Schema::hasTable('dt_projects') || !Schema::hasColumn('dt_projects', 'site_id')) {
            throw new RuntimeException(
                'dt_projects.site_id does not exist. Apply Phase 1C first.'
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

        if (!Schema::hasColumn('dt_agreements', 'site_id')) {
            Schema::table('dt_agreements', function (Blueprint $table) {
                $table->unsignedBigInteger('site_id')
                    ->nullable()
                    ->after('id');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Backfill existing Agreement Site
        |--------------------------------------------------------------------------
        |
        | First choice:
        | Agreement Department -> Department Site
        |
        | Fallback:
        | Existing KLG hospital Site.
        |--------------------------------------------------------------------------
        */

        DB::statement(
            'UPDATE dt_agreements a
             INNER JOIN lt_departments d
                ON d.id = a.department_id
             SET a.site_id = d.site_id
             WHERE a.site_id IS NULL'
        );

        DB::table('dt_agreements')
            ->whereNull('site_id')
            ->update([
                'site_id' => (int) $klangSiteId,
            ]);

        if (DB::table('dt_agreements')->whereNull('site_id')->exists()) {
            throw new RuntimeException(
                'Unable to backfill every Agreement site_id.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate existing Agreement <-> Project links
        |--------------------------------------------------------------------------
        |
        | We must not silently keep cross-Site relationships.
        |--------------------------------------------------------------------------
        */

        if (Schema::hasTable('dt_agreement_project_links')) {
            $crossSiteLinkCount = DB::table(
                'dt_agreement_project_links as link'
            )
                ->join(
                    'dt_agreements as agreement',
                    'agreement.id',
                    '=',
                    'link.agreement_id'
                )
                ->join(
                    'dt_projects as project',
                    'project.id',
                    '=',
                    'link.project_id'
                )
                ->whereColumn(
                    'agreement.site_id',
                    '<>',
                    'project.site_id'
                )
                ->count();

            if ($crossSiteLinkCount > 0) {
                throw new RuntimeException(
                    "Found {$crossSiteLinkCount} Agreement/Project link(s) "
                        . 'where the Agreement and Project belong to different Sites. '
                        . 'Correct those records before completing Phase 1D.'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Agreement number uniqueness
        |--------------------------------------------------------------------------
        |
        | BEFORE:
        | agreement_no globally unique
        |
        | AFTER:
        | site_id + agreement_no unique
        |
        | Allows:
        |
        | KLG / AGR-001
        | AMP / AGR-001
        |--------------------------------------------------------------------------
        */

        $this->dropSingleColumnUniqueIndex('dt_agreements', 'agreement_no');

        /*
        |--------------------------------------------------------------------------
        | Constraints and indexes
        |--------------------------------------------------------------------------
        */

        Schema::table('dt_agreements', function (Blueprint $table) {
            $table->foreign(
                'site_id',
                'fk_agreements_site'
            )
                ->references('id')
                ->on('lt_sites')
                ->restrictOnDelete();

            $table->unique(
                ['site_id', 'agreement_no'],
                'uq_agreements_site_no'
            );

            $table->index(
                ['site_id', 'agreement_status_id'],
                'idx_agreements_site_status'
            );

            $table->index(
                ['site_id', 'department_id'],
                'idx_agreements_site_department'
            );

            $table->index(
                ['site_id', 'owner_user_id'],
                'idx_agreements_site_owner'
            );

            $table->index(
                ['site_id', 'is_current_version'],
                'idx_agreements_site_current'
            );

            $table->index(
                ['site_id', 'expiry_date'],
                'idx_agreements_site_expiry'
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Make site_id mandatory
        |--------------------------------------------------------------------------
        */

        DB::statement(
            'ALTER TABLE dt_agreements
             MODIFY site_id BIGINT UNSIGNED NOT NULL'
        );
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Safety before restoring global agreement_no uniqueness
        |--------------------------------------------------------------------------
        */

        $duplicateAgreementNo = DB::table('dt_agreements')
            ->select(
                'agreement_no',
                DB::raw('COUNT(*) AS total')
            )
            ->groupBy('agreement_no')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicateAgreementNo) {
            throw new RuntimeException(
                'Cannot roll back Site-aware Agreement numbers because '
                    . "agreement_no '{$duplicateAgreementNo->agreement_no}' "
                    . 'exists in more than one Site.'
            );
        }

        Schema::table('dt_agreements', function (Blueprint $table) {
            $table->dropUnique('uq_agreements_site_no');

            $table->dropIndex('idx_agreements_site_status');
            $table->dropIndex('idx_agreements_site_department');
            $table->dropIndex('idx_agreements_site_owner');
            $table->dropIndex('idx_agreements_site_current');
            $table->dropIndex('idx_agreements_site_expiry');

            $table->dropForeign('fk_agreements_site');

            $table->dropColumn('site_id');
        });

        Schema::table('dt_agreements', function (Blueprint $table) {
            $table->unique(
                'agreement_no',
                'dt_agreements_agreement_no_unique'
            );
        });
    }

    /**
     * Drop an existing single-column UNIQUE index without assuming
     * Laravel's generated index name.
     */
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
