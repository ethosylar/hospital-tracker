<?php

namespace Database\Seeders;

use App\Models\Site;
use App\Models\User;
use App\Models\UserSite;
use Illuminate\Database\Seeder;

class ExistingUserSiteSeeder extends Seeder
{
    public function run(): void
    {
        $site = Site::query()
            ->where('code', 'KLG')
            ->firstOrFail();

        $created = 0;
        $skipped = 0;

        User::query()
            ->orderBy('id')
            ->chunkById(100, function ($users) use (
                $site,
                &$created,
                &$skipped
            ) {
                foreach ($users as $user) {
                    $hasAnySite = UserSite::query()
                        ->where('user_id', $user->id)
                        ->exists();

                    /*
                     * Re-running this seeder later must not unexpectedly add
                     * KPJ Klang to a user that already has another site setup.
                     */
                    if ($hasAnySite) {
                        $skipped++;
                        continue;
                    }

                    UserSite::create([
                        'user_id' => (int) $user->id,
                        'site_id' => (int) $site->id,
                        'access_level' => UserSite::LEVEL_MANAGE,
                        'is_primary' => true,
                        'is_active' => true,
                    ]);

                    $created++;
                }
            });

        $this->command?->info(
            "Existing user site assignment completed. Created: {$created}; Skipped: {$skipped}."
        );
    }
}
