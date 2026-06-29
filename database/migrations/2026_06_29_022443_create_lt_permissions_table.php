<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration {
		public function up(): void
		{
			Schema::create('lt_permissions', function (Blueprint $table) {
				$table->id();
				$table->string('code', 100)->unique();
				$table->string('name', 150);
				$table->string('module', 80)->nullable();
				$table->text('description')->nullable();
				$table->unsignedInteger('sort_order')->default(0);
				$table->boolean('is_active')->default(true);
				$table->timestamps();
				
				$table->index(['module', 'is_active'], 'idx_perm_module_active');
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('lt_permissions');
		}
	};	