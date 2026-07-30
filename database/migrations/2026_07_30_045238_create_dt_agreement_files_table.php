<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('dt_agreement_files', function (Blueprint $table) {
				$table->id();
				
				// A document always belongs to the exact Agreement version.
				$table->foreignId('agreement_id')
                ->constrained('dt_agreements')
                ->cascadeOnDelete();
				
				// Reuses the existing physical file storage table.
				$table->foreignId('file_id')
                ->constrained('dt_files')
                ->restrictOnDelete();
				
				$table->foreignId('document_type_id')
                ->constrained('lt_agreement_document_types')
                ->restrictOnDelete();
				
				$table->string('document_version', 80)->nullable();
				$table->date('document_date')->nullable();
				
				// Current within this specific Agreement version and document type.
				$table->boolean('is_current')->default(true);
				$table->boolean('is_executed_copy')->default(false);
				
				// Supports document history across amendments and renewals.
				$table->foreignId('supersedes_agreement_file_id')
                ->nullable()
                ->constrained('dt_agreement_files')
                ->nullOnDelete();
				
				$table->text('notes')->nullable();
				
				$table->foreignId('linked_by_user_id')
                ->constrained('users')
                ->restrictOnDelete();
				
				$table->timestamps();
				
				$table->unique(
                ['agreement_id', 'file_id'],
                'uq_agr_file_agreement_file'
				);
				
				$table->index(
                ['agreement_id', 'document_type_id', 'is_current'],
                'idx_agr_file_type_current'
				);
				
				$table->index(
                ['supersedes_agreement_file_id'],
                'idx_agr_file_supersedes'
				);
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('dt_agreement_files');
		}
	};	