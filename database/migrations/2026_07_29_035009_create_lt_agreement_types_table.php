<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('lt_agreement_types', function (Blueprint $table) {
				$table->id();
				
				$table->foreignId('agreement_category_id')
                ->nullable()
                ->constrained('lt_agreement_categories')
                ->nullOnDelete();
				
				$table->string('code', 60)->unique();
				$table->string('name', 150);
				$table->text('description')->nullable();
				
				$table->unsignedInteger('sort_order')->default(0);
				$table->boolean('is_system_type')->default(false);
				$table->boolean('is_active')->default(true);
				
				$table->timestamps();
				
				$table->index(
                ['agreement_category_id', 'is_active'],
                'idx_agreement_type_category_active'
				);
				
				$table->index(
                ['is_active', 'sort_order'],
                'idx_agreement_type_active_sort'
				);
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('lt_agreement_types');
		}
	};	