<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('lt_agreement_document_types', function (Blueprint $table) {
				$table->id();
				
				$table->string('code', 60)->unique();
				$table->string('name', 150);
				$table->text('description')->nullable();
				
				// Allows OCR to be restricted by document type later.
				$table->boolean('ocr_eligible')->default(true);
				
				$table->unsignedInteger('sort_order')->default(0);
				$table->boolean('is_system_type')->default(false);
				$table->boolean('is_active')->default(true);
				
				$table->timestamps();
				
				$table->index(
                ['is_active', 'sort_order'],
                'idx_agr_doc_type_active_sort'
				);
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('lt_agreement_document_types');
		}
	};	