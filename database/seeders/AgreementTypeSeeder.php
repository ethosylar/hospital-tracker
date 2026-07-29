<?php
	
	namespace Database\Seeders;
	
	use App\Models\AgreementType;
	use Illuminate\Database\Seeder;
	
	class AgreementTypeSeeder extends Seeder
	{
		public function run(): void
		{
			$types = [
            ['code' => 'LICENCE', 'name' => 'Licence', 'sort_order' => 10],
            ['code' => 'SERVICE', 'name' => 'Service', 'sort_order' => 20],
            ['code' => 'TENANCY', 'name' => 'Tenancy', 'sort_order' => 30],
            ['code' => 'OUTSOURCING', 'name' => 'Outsourcing', 'sort_order' => 40],
            ['code' => 'MAINTENANCE', 'name' => 'Maintenance', 'sort_order' => 50],
            ['code' => 'CLINICAL', 'name' => 'Clinical', 'sort_order' => 60],
            ['code' => 'CONSULTANCY', 'name' => 'Consultancy', 'sort_order' => 70],
            ['code' => 'INSURANCE', 'name' => 'Insurance', 'sort_order' => 80],
            ['code' => 'SUPPLY', 'name' => 'Supply', 'sort_order' => 90],
            ['code' => 'CONSTRUCTION', 'name' => 'Construction', 'sort_order' => 100],
			];
			
			foreach ($types as $type) {
				AgreementType::updateOrCreate(
                ['code' => $type['code']],
                [
				'agreement_category_id' => null,
				'name' => $type['name'],
				'description' => null,
				'sort_order' => $type['sort_order'],
				'is_system_type' => true,
				'is_active' => true,
                ]
				);
			}
		}
	}	