<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration {
		public function up(): void
		{
			Schema::table('dt_projects', function (Blueprint $table) {
				$table->unsignedTinyInteger('planned_progress')->default(0)->after('progress'); // Estimated Progress (%)
				$table->date('actual_start_date')->nullable()->after('start_date');            // Actual start
				$table->text('notes')->nullable()->after('description');                       // Notes
				$table->index(['project_category_id', 'planned_progress'], 'idx_proj_cat_planned_prog');
			});
		}
		
		public function down(): void
		{
			Schema::table('dt_projects', function (Blueprint $table) {
				$table->dropIndex('idx_proj_cat_planned_prog');
				$table->dropColumn(['planned_progress', 'actual_start_date', 'notes']);
			});
		}
	};	