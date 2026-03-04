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
			Schema::create('lt_risk_issue_types', function (Blueprint $table) {
				$table->id();
				$table->string('code', 20)->unique(); // RISK, ISSUE
				$table->string('name', 50);
				$table->boolean('is_active')->default(true);
				$table->timestamps();
			});
		}
		
		/**
			* Reverse the migrations.
		*/
		public function down(): void
		{
			Schema::dropIfExists('lt_risk_issue_types');
		}
		
		
	};
