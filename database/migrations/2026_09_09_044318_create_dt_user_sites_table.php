<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dt_user_sites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('site_id')
                ->constrained('lt_sites')
                ->restrictOnDelete();

            /*
             * Site relationship level only.
             * Role/permission remains responsible for WHAT the user may do.
             * This field helps describe WHERE/how broadly they may operate.
             */
            $table->string('access_level', 20)->default('VIEW');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(
                ['user_id', 'site_id'],
                'uq_user_site'
            );

            $table->index(
                ['site_id', 'access_level', 'is_active'],
                'idx_user_sites_site_level_active'
            );

            $table->index(
                ['user_id', 'is_primary', 'is_active'],
                'idx_user_sites_primary_active'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dt_user_sites');
    }
};