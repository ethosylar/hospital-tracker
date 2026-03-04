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
			Schema::create('lt_external_sources', function (Blueprint $table) {
				$table->id();
				$table->string('code', 50)->unique(); // SERVICENOW, JIRA...
				$table->string('name', 150);
				$table->string('base_url')->nullable();
				$table->boolean('is_active')->default(true);
				$table->timestamps();
			});
		}
		
		/**
			* Reverse the migrations.
		*/
		public function down(): void
		{
			Schema::dropIfExists('lt_external_sources');
		}
		
		
	};
