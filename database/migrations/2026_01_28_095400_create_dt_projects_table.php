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
			Schema::create('dt_projects', function (Blueprint $table) {
				$table->id();
				
				$table->string('code', 50)->unique();
				$table->string('name', 255);
				$table->text('description')->nullable();
				
				$table->foreignId('department_id')->constrained('lt_departments');
				$table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
				$table->string('sponsor')->nullable();
				
				$table->foreignId('project_status_id')->constrained('st_project_statuses');
				$table->foreignId('priority_id')->constrained('lt_priorities');
				
				$table->unsignedTinyInteger('progress')->default(0);
				
				$table->date('start_date')->nullable();
				$table->date('target_end_date')->nullable();
				$table->date('actual_end_date')->nullable();
				
				$table->timestamp('last_status_changed_at')->nullable();
				$table->timestamps();
				
				$table->index(['department_id', 'project_status_id']);
				$table->index(['project_status_id', 'target_end_date']);
			});
		}
		
		/**
			* Reverse the migrations.
		*/
		public function down(): void
		{
			Schema::dropIfExists('dt_projects');
		}
		
		
	};
