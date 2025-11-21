<?php

namespace Modules\Bibliodata\Database\Seeders;

use Illuminate\Database\Seeder;

class BibliodataDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class
        ]);
    }
}
