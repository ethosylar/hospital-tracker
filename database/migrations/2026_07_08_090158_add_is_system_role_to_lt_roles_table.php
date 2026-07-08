<?php
	
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;
	use Illuminate\Support\Facades\DB;
	
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::table('lt_roles', function (Blueprint $table) {
				$table->boolean('is_system_role')
                ->default(false)
                ->after('is_active');
				
				$table->index('is_system_role', 'idx_roles_system_role');
			});
			
			DB::table('lt_roles')
            ->where('code', 'ADMIN')
            ->update([
			'is_system_role' => true,
			'is_active' => true,
			'updated_at' => now(),
            ]);
		}
		
		public function down(): void
		{
			Schema::table('lt_roles', function (Blueprint $table) {
				$table->dropIndex('idx_roles_system_role');
				$table->dropColumn('is_system_role');
			});
		}
	};	