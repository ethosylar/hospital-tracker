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
			Schema::create('dt_audit_logs', function (Blueprint $table) {
				$table->id();
				
				$table->string('entity_type', 50);
				$table->unsignedBigInteger('entity_id');
				
				$table->string('action', 30);     // CREATE|UPDATE|DELETE|SYNC|STATUS_CHANGE
				$table->json('changes')->nullable();
				
				$table->foreignId('performed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
				$table->string('source', 30)->default('UI');
				$table->timestamp('performed_at')->useCurrent();
				
				$table->timestamps();
				
				$table->index(['entity_type', 'entity_id']);
				$table->index(['performed_at']);
			});
		}
		
		/**
			* Reverse the migrations.
		*/
		public function down(): void
		{
			Schema::dropIfExists('dt_audit_logs');
		}
		
		
	};
