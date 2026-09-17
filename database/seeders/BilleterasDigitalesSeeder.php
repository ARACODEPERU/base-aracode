<?php

namespace Database\Seeders;

use App\Models\BilleteraDigital;
use Illuminate\Database\Seeder;

/**
 * Catalogo de billeteras digitales del combo "Crear billetera digital"
 * (Configuracion -> Empresa -> Billeteras Digitales).
 *
 * Idempotente: usa firstOrCreate, seguro para re-ejecutar en produccion.
 * La migracion 2026_08_19_000000 ya inserta el catalogo al crear la tabla;
 * este seeder cubre instalaciones donde la tabla existe pero quedo vacia.
 */
class BilleterasDigitalesSeeder extends Seeder
{
    public function run(): void
    {
        $billeteras = [
            ['short_name' => 'YAPE', 'full_name' => 'Yape'],
            ['short_name' => 'PLIN', 'full_name' => 'Plin'],
            ['short_name' => 'TUNKI', 'full_name' => 'Tunki'],
            ['short_name' => 'LUKITA', 'full_name' => 'Lukita'],
            ['short_name' => 'AGIL', 'full_name' => 'Agil BanBif'],
            ['short_name' => 'BITEL WALLET', 'full_name' => 'Bitel Wallet'],
            ['short_name' => 'BIM', 'full_name' => 'BIM'],
            ['short_name' => 'PAGO EFECTIVO', 'full_name' => 'PagoEfectivo'],
        ];

        foreach ($billeteras as $billetera) {
            BilleteraDigital::firstOrCreate(
                ['short_name' => $billetera['short_name']],
                array_merge($billetera, ['status' => true])
            );
        }
    }
}
