<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	
	return new class extends Migration {
		public function up(): void
		{
			Schema::create('lt_role_permissions', function (Blueprint $table) {
				$table->id();
				
				$table->foreignId('role_id')
                ->constrained('lt_roles')
                ->cascadeOnDelete();
				
				$table->foreignId('permission_id')
                ->constrained('lt_permissions')
                ->cascadeOnDelete();
				
				$table->timestamps();
				
				$table->unique(['role_id', 'permission_id'], 'uq_role_permission');
			});
		}
		
		public function down(): void
		{
			Schema::dropIfExists('lt_role_permissions');
		}
	};	