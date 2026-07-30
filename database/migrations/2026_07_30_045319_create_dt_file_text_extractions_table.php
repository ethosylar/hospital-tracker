<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('dt_file_text_extractions', function (Blueprint $table) {
				$table->id();
				
				// One current extraction state/result per stored physical file.
				$table->foreignId('file_id')
                ->unique()
                ->constrained('dt_files')
                ->cascadeOnDelete();
				
				$table->string('status', 30)->default('NOT_REQUESTED');
				$table->string('engine', 100)->nullable();
				$table->string('language', 30)->default('eng');
				
				// Used to detect whether the source file changed.
				$table->string('source_checksum', 64)->nullable();
				
				// Empty until a future approved OCR/text-extraction engine runs.
				$table->longText('extracted_text')->nullable();
				$table->unsignedInteger('page_count')->nullable();
				$table->text('error_message')->nullable();
				
				$table->foreignId('requested_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
				
				$table->timestamp('requested_at')->nullable();
				$table->timestamp('started_at')->nullable();
				$table->timestamp('completed_at')->nullable();
				
				$table->timestamps();
				
				$table->index('status', 'idx_file_extract_status');
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('dt_file_text_extractions');
		}
	};	