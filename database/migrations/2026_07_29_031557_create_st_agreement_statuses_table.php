<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('st_agreement_statuses', function (Blueprint $table) {
				$table->id();
				$table->string('code', 50)->unique();
				$table->string('name', 120);
				$table->text('description')->nullable();
				$table->unsignedInteger('sort_order')->default(0);
				$table->boolean('is_terminal')->default(false);
				$table->boolean('is_system_status')->default(false);
				$table->boolean('is_active')->default(true);
				$table->timestamps();
				$table->index(['is_active', 'sort_order'], 'idx_agreement_status_active_sort');
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('st_agreement_statuses');
		}
	};
