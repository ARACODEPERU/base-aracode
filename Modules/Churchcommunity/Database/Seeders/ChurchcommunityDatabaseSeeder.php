<?php

namespace Modules\Churchcommunity\Database\Seeders;

use Illuminate\Database\Seeder;

class ChurchcommunityDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            PermissionChurchcommunitySeeder::class,
            SedesChurchcommunitySeeder::class
        ]);
    }
}
