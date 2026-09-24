<?php

namespace Database\Seeders;

use App\Models\LocalSale;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EstablishmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Idempotente: firstOrCreate por descripción. Varias tablas (users, series,
     * caja) apuntan a local_id = 1, así que este local debe existir siempre y una
     * sola vez.
     */
    public function run(): void
    {
        LocalSale::firstOrCreate(
            ['description' => 'Local Principal'],
            [
                'address'    => 'Chimbote',
                'phone'      => '99999999',
                'ubigeo'     => '021801',
                'sunat_code' => '0000',
            ]
        );
    }
}
