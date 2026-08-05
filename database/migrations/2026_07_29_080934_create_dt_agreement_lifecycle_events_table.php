<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('dt_agreement_lifecycle_events', function (Blueprint $table) {
				$table->id();
				
				$table->foreignId('agreement_id')
                ->constrained('dt_agreements')
                ->cascadeOnDelete();
				
				$table->string('event_type', 40);
				
				$table->foreignId('from_status_id')
                ->nullable()
                ->constrained('st_agreement_statuses')
                ->nullOnDelete();
				
				$table->foreignId('to_status_id')
                ->nullable()
                ->constrained('st_agreement_statuses')
                ->nullOnDelete();
				
				$table->foreignId('related_agreement_id')
                ->nullable()
                ->constrained('dt_agreements')
                ->nullOnDelete();
				
				$table->foreignId('performed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
				
				$table->text('reason')->nullable();
				$table->json('metadata')->nullable();
				$table->timestamp('event_at');
				$table->timestamps();
				
				$table->index(
                ['agreement_id', 'event_at'],
                'idx_agreement_event_time'
				);
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('dt_agreement_lifecycle_events');
		}
	};	