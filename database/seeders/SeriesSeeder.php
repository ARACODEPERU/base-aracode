<?php

namespace Database\Seeders;

use App\Models\Serie;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SeriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Idempotente: la serie NV01 (nota de venta, tipo de documento 5) del local 1.
     */
    public function run(): void
    {
        Serie::firstOrCreate(
            [
                'document_type_id' => 5,
                'description'      => 'NV01',
                'local_id'         => 1,
            ],
            ['number' => 1]
        );
    }
}
