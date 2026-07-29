<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('lt_agreement_categories', function (Blueprint $table) {
				$table->id();
				$table->string('code', 60)->unique();
				$table->string('name', 150);
				$table->text('description')->nullable();
				
				$table->unsignedInteger('sort_order')->default(0);
				$table->boolean('is_system_category')->default(false);
				$table->boolean('is_active')->default(true);
				
				$table->timestamps();
				
				$table->index(
                ['is_active', 'sort_order'],
                'idx_agreement_category_active_sort'
				);
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('lt_agreement_categories');
		}
	};	