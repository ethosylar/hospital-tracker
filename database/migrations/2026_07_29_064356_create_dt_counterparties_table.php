<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('dt_counterparties', function (Blueprint $table) {
				$table->id();
				$table->string('code', 50)->unique();
				$table->string('counterparty_type', 30)->default('COMPANY');
				
				$table->string('legal_name', 255);
				$table->string('normalized_name', 255)->index();
				$table->string('trading_name', 255)->nullable();
				
				$table->string('registration_no', 100)->nullable()->unique();
				$table->string('tax_no', 100)->nullable();
				$table->string('vendor_no', 100)->nullable();
				
				$table->string('contact_person', 255)->nullable();
				$table->string('contact_position', 255)->nullable();
				$table->string('email', 255)->nullable();
				$table->string('phone', 50)->nullable();
				$table->string('alternate_phone', 50)->nullable();
				
				$table->string('address_line_1', 255)->nullable();
				$table->string('address_line_2', 255)->nullable();
				$table->string('city', 120)->nullable();
				$table->string('state', 120)->nullable();
				$table->string('postcode', 30)->nullable();
				$table->string('country', 120)->default('Malaysia');
				
				$table->text('notes')->nullable();
				$table->boolean('is_active')->default(true);
				$table->timestamps();
				
				$table->index(
                ['counterparty_type', 'is_active'],
                'idx_counterparty_type_active'
				);
				
				$table->index(
                ['is_active', 'legal_name'],
                'idx_counterparty_active_name'
				);
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('dt_counterparties');
		}
	};	