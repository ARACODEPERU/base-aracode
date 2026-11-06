<?php

namespace Modules\Sales\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Sales\Support\GuiaRemisionPermissions;

class GuiaRemisionPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $created = GuiaRemisionPermissions::sync();

        if (isset($this->command)) {
            $this->command->info('Permisos de Guías de Remisión listos: '.implode(', ', $created));
        }
    }
}
