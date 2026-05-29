<?php

namespace Modules\Socialevents\Database\Seeders;

use App\Models\LocalSale;
use Illuminate\Database\Seeder;
use Modules\Socialevents\Entities\EvenLocalRentalRate;

class LocalRentalRatesSeeder extends Seeder
{
    public function run(): void
    {
        $locals = LocalSale::query()->get(['id', 'description']);

        foreach ($locals as $local) {
            EvenLocalRentalRate::query()->firstOrCreate(
                [
                    'local_id' => $local->id,
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
