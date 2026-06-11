<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration {
		public function up(): void
		{
			Schema::create('dt_project_budget_allocations', function (Blueprint $table) {
				$table->id();
				
				$table->foreignId('project_id')->constrained('dt_projects')->cascadeOnDelete();
				$table->foreignId('budget_line_id')->constrained('dt_project_budget_lines')->cascadeOnDelete();
				
				// allocation targets (either one is allowed; both allowed if you want)
				$table->foreignId('task_id')->nullable()->constrained('dt_project_tasks')->nullOnDelete();
				$table->foreignId('milestone_id')->nullable()->constrained('dt_project_milestones')->nullOnDelete();
				
				$table->decimal('planned_amount', 15, 2)->default(0);
				$table->decimal('actual_amount', 15, 2)->default(0);
				$table->decimal('committed_amount', 15, 2)->default(0);
				
				$table->unsignedInteger('sort_order')->default(0);
				$table->boolean('is_active')->default(true);
				$table->text('notes')->nullable();
				
				$table->timestamps();
				
				$table->index(['project_id', 'budget_line_id'], 'idx_alloc_project_line');
				$table->index(['project_id', 'task_id'], 'idx_alloc_project_task');
				$table->index(['project_id', 'milestone_id'], 'idx_alloc_project_milestone');
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('dt_project_budget_allocations');
		}
	};
