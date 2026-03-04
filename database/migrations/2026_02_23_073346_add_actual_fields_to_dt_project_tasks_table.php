<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::table('dt_project_tasks', function (Blueprint $table) {
				// UI
				$table->string('task_color', 20)->nullable()->after('description'); // e.g. #FFAA00
				
				// actual timeline
				$table->date('actual_start_date')->nullable()->after('end_date');
				$table->date('actual_end_date')->nullable()->after('actual_start_date');
				
				// actual status (separate from planned/current status)
				$table->foreignId('actual_task_status_id')
                ->nullable()
                ->after('task_status_id')
                ->constrained('st_task_statuses')
                ->nullOnDelete();
				
				// duration (days) - keep simple MVP, can be derived but you requested a column
				$table->unsignedInteger('duration')->default(0)->after('end_date');
				
				$table->index(['project_id', 'actual_start_date']);
				$table->index(['project_id', 'actual_end_date']);
			});
		}
		
		public function down(): void
		{
			Schema::table('dt_project_tasks', function (Blueprint $table) {
				$table->dropIndex(['project_id', 'actual_start_date']);
				$table->dropIndex(['project_id', 'actual_end_date']);
				
				$table->dropConstrainedForeignId('actual_task_status_id');
				$table->dropColumn(['task_color', 'actual_start_date', 'actual_end_date', 'duration']);
			});
		}
	};
