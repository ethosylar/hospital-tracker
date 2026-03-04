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
			Schema::create('dt_external_risk_issues', function (Blueprint $table) {
				$table->id();
				
				$table->foreignId('external_source_id')->nullable()->constrained('lt_external_sources')->nullOnDelete();
				$table->string('external_id', 120);
				
				$table->foreignId('project_id')->nullable()->constrained('dt_projects')->nullOnDelete();
				$table->foreignId('type_id')->constrained('lt_risk_issue_types');
				
				$table->string('title', 255);
				$table->text('description')->nullable();
				
				$table->foreignId('severity_id')->constrained('st_severities');
				$table->foreignId('risk_issue_status_id')->constrained('st_risk_issue_statuses');
				
				$table->string('owner')->nullable();
				$table->timestamp('source_created_at')->nullable();
				$table->timestamp('source_updated_at')->nullable();
				$table->timestamp('last_synced_at')->nullable();
				$table->json('raw_payload')->nullable();
				
				$table->timestamps();
				
				$table->unique(['external_source_id', 'external_id']);
				$table->index(
				['project_id', 'severity_id', 'risk_issue_status_id'],
				'idx_ext_ri_proj_sev_stat'
				);
			});
		}
		
		/**
			* Reverse the migrations.
		*/
		public function down(): void
		{
			Schema::dropIfExists('dt_external_risk_issues');
		}
		
		
	};
