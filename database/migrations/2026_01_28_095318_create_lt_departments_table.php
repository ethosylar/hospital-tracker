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
			Schema::create('lt_departments', function (Blueprint $table) {
				$table->id();
				$table->string('code', 50)->unique(); // IT, CLIN, FAC...
				$table->string('name', 150);
				$table->boolean('is_active')->default(true);
				$table->timestamps();
			});
		}
		
		/**
			* Reverse the migrations.
		*/
		public function down(): void
		{
			Schema::dropIfExists('lt_departments');
		}
	};
