<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('dt_external_permits', function (Blueprint $table) {
				$table->id();
				
				$table->foreignId('external_source_id')
                ->constrained('lt_external_sources')
                ->restrictOnDelete();
				
				/*
					* IDs originating from ePTW.
					*
					* Use varchar instead of integer because form.id is displayed
					* using zero padding, for example 00110.
				*/
				$table->string('external_form_id', 50);
				$table->string('external_permit_id', 50)->nullable();
				
				// Preserve original and normalized statuses.
				$table->string('raw_status', 50)->nullable();
				$table->string('normalized_status', 30)->default('UNKNOWN');
				
				// Non-sensitive permit summary.
				$table->string('applicant_name', 150)->nullable();
				$table->string('service_name', 255)->nullable();
				$table->string('company_name', 500)->nullable();
				$table->string('supervisor_name', 150)->nullable();
				$table->string('exact_location', 255)->nullable();
				
				$table->text('work_type')->nullable();
				$table->text('hazards')->nullable();
				$table->text('ppe')->nullable();
				$table->text('worksite_controls')->nullable();
				$table->text('infection_controls')->nullable();
				$table->text('remark')->nullable();
				
				// Permit working period.
				$table->date('work_start_date')->nullable();
				$table->date('work_end_date')->nullable();
				$table->time('work_start_time')->nullable();
				$table->time('work_end_time')->nullable();
				
				// Safety briefing summary.
				$table->date('brief_date')->nullable();
				$table->time('brief_time')->nullable();
				$table->string('brief_conducted_by', 150)->nullable();
				
				// Source tracking.
				$table->dateTime('source_created_at')->nullable();
				$table->dateTime('source_updated_at')->nullable();
				
				$table->timestamp('last_synced_at')->nullable();
				$table->timestamp('last_seen_at')->nullable();
				
				$table->string('source_url', 1000)->nullable();
				
				/*
					* Used to detect changed records while the legacy ePTW system
					* does not have reliable updated_at columns.
				*/
				$table->char('source_hash', 64)->nullable();
				
				// Support source-side deleted records without deleting history.
				$table->boolean('is_source_deleted')->default(false);
				$table->timestamp('source_deleted_at')->nullable();
				
				$table->timestamps();
				
				$table->unique(
                ['external_source_id', 'external_form_id'],
                'uq_ext_permit_source_form'
				);
				
				$table->index(
                ['external_source_id', 'normalized_status'],
                'idx_ext_permit_source_status'
				);
				
				$table->index(
                ['work_start_date', 'work_end_date'],
                'idx_ext_permit_work_dates'
				);
				
				$table->index('last_synced_at', 'idx_ext_permit_synced');
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('dt_external_permits');
		}
	};	