<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lt_sites')) {
            throw new \RuntimeException(
                'lt_sites does not exist. Run the Phase 1A multi-site migrations first.'
            );
        }

        $defaultSiteId = DB::table('lt_sites')
            ->where('code', 'KLG')
            ->value('id');

        if (!$defaultSiteId) {
            throw new \RuntimeException(
                'Default site KLG was not found. Run SiteSeeder before this migration.'
            );
        }

        Schema::table('lt_departments', function (Blueprint $table) {
            $table->unsignedBigInteger('site_id')
                ->nullable()
                ->after('id');
        });

        DB::table('lt_departments')
            ->whereNull('site_id')
            ->update([
                'site_id' => (int) $defaultSiteId,
            ]);

        Schema::table('lt_departments', function (Blueprint $table) {
            $table->dropUnique('lt_departments_code_unique');
        });

        // All legacy rows have now been backfilled to KLG, so site_id can be required.
        DB::statement(
            'ALTER TABLE lt_departments ' .
                'MODIFY site_id BIGINT UNSIGNED NOT NULL'
        );

        Schema::table('lt_departments', function (Blueprint $table) {
            $table->foreign('site_id', 'lt_departments_site_id_foreign')
                ->references('id')
                ->on('lt_sites')
                ->restrictOnDelete();

            $table->unique(
                ['site_id', 'code'],
                'uq_departments_site_code'
            );

            $table->index(
                ['site_id', 'is_active', 'name'],
                'idx_departments_site_active_name'
            );
        });
    }

    public function down(): void
    {
        $hasDuplicateCodes = DB::table('lt_departments')
            ->select('code')
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicateCodes) {
            throw new \RuntimeException(
                'Cannot roll back site-aware departments because duplicate department codes now exist across sites.'
            );
        }

        Schema::table('lt_departments', function (Blueprint $table) {
            $table->dropForeign('lt_departments_site_id_foreign');
            $table->dropUnique('uq_departments_site_code');
            $table->dropIndex('idx_departments_site_active_name');
            $table->dropColumn('site_id');
        });

        Schema::table('lt_departments', function (Blueprint $table) {
            $table->unique('code', 'lt_departments_code_unique');
        });
    }
};
