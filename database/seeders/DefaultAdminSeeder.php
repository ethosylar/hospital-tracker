<?php
	
	namespace Database\Seeders;
	
	use Illuminate\Database\Seeder;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Hash;
	
	class DefaultAdminSeeder extends Seeder
	{
		/**
			* Run the database seeds.
		*/
		public function run(): void
		{
			// Create admin user (if not exists)
			DB::table('users')->updateOrInsert(
			['email' => 'admin@hospital.local'],
			[
			'username' => 'admin',
			'name' => 'System Admin',
			'password' => Hash::make('Admin@1234'),
			'updated_at' => now(),
			'created_at' => now(),
			]
			);
			
			
			$userId = DB::table('users')->where('email', 'admin@hospital.local')->value('id');
			$roleId = DB::table('lt_roles')->where('code', 'ADMIN')->value('id');
			
			
			if ($userId && $roleId) {
				DB::table('dt_user_roles')->updateOrInsert(
				['user_id' => $userId, 'role_id' => $roleId],
				['updated_at' => now(), 'created_at' => now()]
				);
			}
		}
	}
