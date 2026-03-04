<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration
	{
		/**
			* Run the migrations.
		*/
		public function up(): void
		{
			Schema::create('dt_project_tasks', function (Blueprint $table) {
				$table->id();
				
				$table->foreignId('project_id')->constrained('dt_projects')->cascadeOnDelete();
				$table->foreignId('parent_task_id')->nullable()->constrained('dt_project_tasks')->nullOnDelete();
				
				$table->string('name', 255);
				$table->text('description')->nullable();
				
				$table->foreignId('task_status_id')->constrained('st_task_statuses');
				$table->unsignedTinyInteger('progress')->default(0);
				
				$table->date('start_date')->nullable();
				$table->date('end_date')->nullable();
				
				$table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
				$table->unsignedInteger('sort_order')->default(0);
				$table->foreignId('depends_on_task_id')->nullable()->constrained('dt_project_tasks')->nullOnDelete();
				
				$table->timestamps();
				
				$table->index(['project_id', 'task_status_id']);
				$table->index(['project_id', 'start_date']);
				$table->index(['project_id', 'end_date']);
			});
		}
		
		/**
			* Reverse the migrations.
		*/
		public function down(): void
		{
			Schema::dropIfExists('dt_project_tasks');
		}
		
		
	};
