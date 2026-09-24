<?php

namespace Modules\Churchcommunity\Database\Seeders;

use App\Models\Sede;
use Illuminate\Database\Seeder;
use Modules\Churchcommunity\Entities\CigleMemberType;

class SedesChurchcommunitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'description' => 'Principal',
                'address' => 'Dirección de la Sede 1',
            ],
            [
                'description' => 'Lima Sur',
                'address' => 'Dirección de la Sede 2',
            ],
            [
                'description' => 'Lima Norte',
                'address' => 'Dirección de la Sede 3',
            ],
            [
                'description' => 'Lima Centro',
                'address' => 'Dirección de la Sede 4',
            ],
            [
                'description' => 'Sarita Colonia',
                'address' => 'Dirección de la Sede 5',
            ],
            [
                'description' => 'Ventanilla',
                'address' => 'Dirección de la Sede 6',
            ],
            [
                'description' => 'New Yersey',
                'address' => 'Dirección de la Sede 7',
            ],
            [
                'description' => 'Chicago',
                'address' => 'Dirección de la Sede 8',
            ],
            [
                'description' => 'Madrid',
                'address' => 'Dirección de la Sede 9',
            ],
            [
                'description' => 'Barcelona',
                'address' => 'Dirección de la Sede 10',
            ],
            [
                'description' => 'Valencia',
                'address' => 'Dirección de la Sede 11',
            ],
        ];

        // firstOrCreate por descripción: el seeder se puede reejecutar sin duplicar sedes.
        foreach ($data as $record) {
            Sede::firstOrCreate(['description' => $record['description']], $record);
        }

        $levels = [
            ['description' => 'Creyente'],
            ['description' => 'Discípulo'],
            ['description' => 'Sub Líder'],
            ['description' => 'Líder'],
            ['description' => 'Mentor'],
            ['description' => 'Anciano'],
            ['description' => 'Ministro'],
            ['description' => 'Efesio'], //(Pastor, Evangelista, Profeta)
            ['description' => 'Apóstol'] //(Padre espiritual)
        ];

        // firstOrCreate por descripción: el seeder se puede reejecutar sin duplicar niveles.
        foreach ($levels as $level) {
            CigleMemberType::firstOrCreate(['description' => $level['description']], $level);
        }
    }

}
