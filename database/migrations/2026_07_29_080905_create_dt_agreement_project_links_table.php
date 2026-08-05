<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('dt_agreement_project_links', function (Blueprint $table) {
				$table->id();
				
				$table->foreignId('agreement_id')
                ->constrained('dt_agreements')
                ->cascadeOnDelete();
				
				$table->foreignId('project_id')
                ->constrained('dt_projects')
                ->cascadeOnDelete();
				
				$table->foreignId('linked_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
				
				$table->text('notes')->nullable();
				$table->timestamps();
				
				$table->unique(
                ['agreement_id', 'project_id'],
                'uq_agreement_project'
				);
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('dt_agreement_project_links');
		}
	};	