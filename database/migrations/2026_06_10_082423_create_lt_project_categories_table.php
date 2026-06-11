<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('lt_project_categories', function (Blueprint $table) {
				$table->id();
				
				$table->string('code', 50)->unique();     // CAPEX_2023
				$table->string('name', 150);              // CAPEX 2023
				$table->string('group', 20)->nullable();  // CAPEX / OPEX (optional)
				$table->unsignedSmallInteger('year')->nullable(); // 2023 (optional)
				
				$table->unsignedInteger('sort_order')->default(0);
				$table->boolean('is_active')->default(true);
				
				$table->timestamps();
				
				$table->index(['is_active', 'sort_order']);
				$table->index(['group', 'year']);
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('lt_project_categories');
		}
	};	