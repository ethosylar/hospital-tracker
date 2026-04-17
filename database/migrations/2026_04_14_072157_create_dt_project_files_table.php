<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dt_project_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('project_id')
                ->constrained('dt_projects')
                ->cascadeOnDelete();

            $table->foreignId('file_id')
                ->constrained('dt_files')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['project_id', 'file_id']);
            $table->index(['project_id']);
            $table->index(['file_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dt_project_files');
    }
};