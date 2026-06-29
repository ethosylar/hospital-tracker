<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('dt_integration_sync_runs', function (Blueprint $table) {
				$table->id();
				
				$table->foreignId('external_source_id')
                ->constrained('lt_external_sources')
                ->restrictOnDelete();
				
				$table->string('integration_code', 50);
				$table->string('sync_type', 20)->default('MANUAL');
				$table->string('status', 20)->default('RUNNING');
				
				$table->dateTime('started_at');
				$table->dateTime('completed_at')->nullable();
				
				$table->unsignedInteger('fetched_count')->default(0);
				$table->unsignedInteger('created_count')->default(0);
				$table->unsignedInteger('updated_count')->default(0);
				$table->unsignedInteger('unchanged_count')->default(0);
				$table->unsignedInteger('deleted_count')->default(0);
				$table->unsignedInteger('failed_count')->default(0);
				
				$table->string('cursor_from', 255)->nullable();
				$table->string('cursor_to', 255)->nullable();
				
				$table->text('error_message')->nullable();
				
				$table->foreignId('triggered_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
				
				$table->timestamps();
				
				$table->index(
                ['integration_code', 'status'],
                'idx_sync_code_status'
				);
				
				$table->index(
                ['external_source_id', 'started_at'],
                'idx_sync_source_started'
				);
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('dt_integration_sync_runs');
		}
	};	