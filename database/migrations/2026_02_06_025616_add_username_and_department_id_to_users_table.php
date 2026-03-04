<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // login via username OR email
            $table->string('username', 100)->nullable()->unique()->after('name');

            // relationship to lt_departments
            $table->foreignId('department_id')
                ->nullable()
                ->constrained('lt_departments')   // references lt_departments.id
                ->nullOnDelete()
                ->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn('username');
        });
    }
};
