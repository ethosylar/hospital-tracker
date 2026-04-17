<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dt_task_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->constrained('dt_project_tasks')
                ->cascadeOnDelete();

            $table->foreignId('file_id')
                ->constrained('dt_files')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['task_id', 'file_id']);
            $table->index(['task_id']);
            $table->index(['file_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dt_task_files');
    }
};