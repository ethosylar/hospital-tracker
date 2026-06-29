<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('dt_project_permit_links', function (Blueprint $table) {
				$table->id();
				
				/*
					* Internal Hospital Tracker permit ID.
					* This is dt_external_permits.id, not the ePTW permit number.
				*/
				$table->foreignId('permit_id')
                ->constrained('dt_external_permits')
                ->cascadeOnDelete();
				
				$table->foreignId('project_id')
                ->constrained('dt_projects')
                ->cascadeOnDelete();
				
				/*
					* Null means project-level link.
					* A populated task_id means task-level link.
				*/
				$table->foreignId('task_id')
                ->nullable()
                ->constrained('dt_project_tasks')
                ->cascadeOnDelete();
				
				$table->foreignId('linked_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
				
				$table->timestamp('linked_at')->nullable();
				$table->text('notes')->nullable();
				$table->boolean('is_active')->default(true);
				
				$table->timestamps();
				
				$table->unique(
                ['permit_id', 'project_id', 'task_id'],
                'uq_permit_project_task'
				);
				
				$table->index(
                ['project_id', 'is_active'],
                'idx_permit_link_project'
				);
				
				$table->index(
                ['task_id', 'is_active'],
                'idx_permit_link_task'
				);
				
				$table->index(
                ['permit_id', 'is_active'],
                'idx_permit_link_permit'
				);
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('dt_project_permit_links');
		}
	};	