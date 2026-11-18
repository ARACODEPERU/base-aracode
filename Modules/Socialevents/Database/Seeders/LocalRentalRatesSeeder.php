<?php

namespace Modules\Socialevents\Database\Seeders;

use App\Models\LocalSale;
use Illuminate\Database\Seeder;
use Modules\Socialevents\Entities\EvenLocalArea;
use Modules\Socialevents\Entities\EvenLocalRentalRate;

class LocalRentalRatesSeeder extends Seeder
{
    public function run(): void
    {
        $locals = LocalSale::query()->get(['id', 'description']);

        foreach ($locals as $local) {
            $hall = EvenLocalArea::query()->firstOrCreate(
                [
                    'local_id' => $local->id,
                    'area_type' => EvenLocalArea::TYPE_EVENT_HALL,
                ],
                [
                    'name' => 'Salón de eventos',
                    'is_active' => true,
                    'sort_order' => 1,
                ]
            );

            EvenLocalRentalRate::query()->firstOrCreate(
                [
                    'local_id' => $local->id,
                    'area_id' => $hall->id,
                    'name' => 'Tarifa estándar',
                ],
                [
                    'hourly_rate' => 150.00,
                    'is_active' => true,
                ]
            );
        }
    }
}
