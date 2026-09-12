<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lt_sites', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->string('short_name', 150)->nullable();
            $table->string('site_type', 30)->default('HOSPITAL');

            $table->string('address_line_1', 255)->nullable();
            $table->string('address_line_2', 255)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->string('postcode', 30)->nullable();
            $table->string('country', 120)->default('Malaysia');

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['site_type', 'is_active'], 'idx_sites_type_active');
            $table->index(['is_active', 'name'], 'idx_sites_active_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lt_sites');
    }
};
