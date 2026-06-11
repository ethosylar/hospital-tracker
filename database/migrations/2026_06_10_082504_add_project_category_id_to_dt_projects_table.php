<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::table('dt_projects', function (Blueprint $table) {
				$table->foreignId('project_category_id')
                ->nullable()
                ->after('department_id')
                ->constrained('lt_project_categories')
                ->nullOnDelete();
				
				$table->index(['project_category_id'], 'idx_projects_category');
			});
		}
		
		public function down(): void
		{
			Schema::table('dt_projects', function (Blueprint $table) {
				$table->dropIndex('idx_projects_category');
				$table->dropConstrainedForeignId('project_category_id');
			});
		}
	};	