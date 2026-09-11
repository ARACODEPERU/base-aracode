<?php

namespace Modules\Treasury\Database\Seeders;

use Illuminate\Database\Seeder;

class TreasuryDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionsModuleSeeder::class,
        ]);
    }
}
