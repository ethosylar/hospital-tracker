<?php

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::query()->firstOrCreate(
            ['code' => 'KLG'],
            [
                'name' => 'KPJ Klang Specialist Hospital',
                'short_name' => 'KPJ Klang',
                'site_type' => 'HOSPITAL',
                'country' => 'Malaysia',
                'is_active' => true,
            ]
        );

        $this->command?->info(
            "Site ready: {$site->code} - {$site->name}"
        );
    }
}
