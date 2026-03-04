<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dt_project_tasks', function (Blueprint $table) {
            $table->foreignId('milestone_id')
                ->nullable()
                ->after('project_id')
                ->constrained('dt_project_milestones')
                ->nullOnDelete();

            $table->index(['project_id', 'milestone_id'], 'idx_task_project_milestone');
            $table->index(['milestone_id', 'sort_order'], 'idx_task_milestone_sort');
        });
    }

    public function down(): void
    {
        Schema::table('dt_project_tasks', function (Blueprint $table) {
            $table->dropIndex('idx_task_project_milestone');
            $table->dropIndex('idx_task_milestone_sort');

            $table->dropForeign(['milestone_id']);
            $table->dropColumn('milestone_id');
        });
    }
};