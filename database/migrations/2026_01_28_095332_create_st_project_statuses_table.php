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
			Schema::create('st_project_statuses', function (Blueprint $table) {
				$table->id();
				$table->string('code', 30)->unique();   // PLANNED, IN_PROGRESS...
				$table->string('name', 80);
				$table->unsignedInteger('sort_order')->default(0);
				$table->boolean('is_active')->default(true);
				$table->timestamps();
			});
		}
		
		/**
			* Reverse the migrations.
		*/
		public function down(): void
		{
			Schema::dropIfExists('st_project_statuses');
		}
		
		
	};
