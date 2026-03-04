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
			Schema::create('dt_project_milestones', function (Blueprint $table) {
				$table->id();
				$table->foreignId('project_id')->constrained('dt_projects')->cascadeOnDelete();
				
				$table->string('name', 255);
				$table->date('milestone_date');
				$table->string('status', 30)->default('PENDING'); // keep simple in MVP
				$table->timestamps();
				
				$table->index(['project_id', 'milestone_date']);
			});
		}
		
		/**
			* Reverse the migrations.
		*/
		public function down(): void
		{
			Schema::dropIfExists('dt_project_milestones');
		}
		
		
	};
