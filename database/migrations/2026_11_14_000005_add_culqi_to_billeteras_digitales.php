<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega Culqi al catalogo de billeteras digitales que alimenta el combo
 * "Billetera" del modal de Crear billetera digital (Configuracion -> Empresa).
 *
 * La migracion original del catalogo ya se ejecuto, por eso el alta va aqui.
 * Es idempotente: si Culqi ya existe, no hace nada.
 */
return new class extends Migration
{
    private const SHORT_NAME = 'CULQI';

    public function up(): void
    {
        if (! Schema::hasTable('billeteras_digitales')) {
            return;
        }

        if (DB::table('billeteras_digitales')->where('short_name', self::SHORT_NAME)->exists()) {
            return;
        }

        DB::table('billeteras_digitales')->insert([
            'short_name' => self::SHORT_NAME,
            'full_name'  => 'Culqi',
            'status'     => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('billeteras_digitales')) {
            return;
        }

        $ids = DB::table('billeteras_digitales')->where('short_name', self::SHORT_NAME)->pluck('id');

        // Si alguna empresa ya la usa como billetera, se conserva: borrarla
        // dejaria sus cuentas sin catalogo.
        $enUso = Schema::hasTable('company_billeteras')
            && DB::table('company_billeteras')->whereIn('billetera_id', $ids)->exists();

        if ($enUso) {
            return;
        }

        DB::table('billeteras_digitales')->whereIn('id', $ids)->delete();
    }
};
