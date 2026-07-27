<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('dt_external_risk_issue_links', function (Blueprint $table) {
				$table->id();
				
				$table->foreignId('external_risk_issue_id')
                ->constrained('dt_external_risk_issues')
                ->cascadeOnDelete();
				
				$table->foreignId('project_id')
                ->nullable()
                ->constrained('dt_projects')
                ->nullOnDelete();
				
				$table->foreignId('task_id')
                ->nullable()
                ->constrained('dt_project_tasks')
                ->nullOnDelete();
				
				$table->foreignId('milestone_id')
                ->nullable()
                ->constrained('dt_project_milestones')
                ->nullOnDelete();
				
				$table->foreignId('permit_id')
                ->nullable()
                ->constrained('dt_external_permits')
                ->nullOnDelete();
				
				$table->foreignId('linked_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
				
				$table->timestamp('linked_at')->nullable();
				$table->text('notes')->nullable();
				$table->boolean('is_active')->default(true);
				$table->timestamps();
				
				$table->index(['external_risk_issue_id', 'is_active'], 'idx_ext_risk_link_issue_active');
				$table->index(['project_id', 'is_active'], 'idx_ext_risk_link_project_active');
				$table->index(['task_id', 'is_active'], 'idx_ext_risk_link_task_active');
				$table->index(['milestone_id', 'is_active'], 'idx_ext_risk_link_milestone_active');
				$table->index(['permit_id', 'is_active'], 'idx_ext_risk_link_permit_active');
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('dt_external_risk_issue_links');
		}
	};
