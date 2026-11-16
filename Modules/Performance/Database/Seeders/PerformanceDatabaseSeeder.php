<?php

namespace Modules\Performance\Database\Seeders;

use Illuminate\Database\Seeder;

class PerformanceDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionsPerformanceSeeder::class,
        ]);
    }
}
