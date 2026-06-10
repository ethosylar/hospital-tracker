<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration {
		public function up(): void
		{
			Schema::create('dt_project_budget_lines', function (Blueprint $table) {
				$table->id();
				$table->foreignId('project_id')->constrained('dt_projects')->cascadeOnDelete();
				
				// COST or FUNDING
				$table->string('line_type', 10)->default('COST'); // COST | FUNDING
				
				// category / item
				$table->string('code', 50);
				$table->string('name', 150);
				
				// amounts
				$table->decimal('planned_amount', 15, 2)->default(0);
				$table->decimal('actual_amount', 15, 2)->default(0);
				$table->decimal('committed_amount', 15, 2)->default(0); // mostly for COST
				
				$table->unsignedInteger('sort_order')->default(0);
				$table->boolean('is_active')->default(true);
				$table->text('notes')->nullable();
				
				$table->timestamps();
				
				$table->unique(['project_id', 'line_type', 'code'], 'uq_proj_line_type_code');
				$table->index(['project_id', 'line_type']);
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('dt_project_budget_lines');
		}
	};	