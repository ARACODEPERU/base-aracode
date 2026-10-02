<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parametro del interruptor multi-colegio (serie PTM, mismo patron de
     * PTM0004 multi-moneda) + colegio por defecto para el modo mono-colegio.
     */
    public function up(): void
    {
        $exists = DB::table('parameters')->where('parameter_code', 'PTM0005')->exists();

        if (! $exists) {
            DB::table('parameters')->insert([
                'parameter_code' => 'PTM0005',
                'description' => 'Trabajo con multiples colegios en academico (0 = un solo colegio, 1 = multiples colegios)',
                'control_type' => 'in',
                'value_default' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('aca_schools') && DB::table('aca_schools')->count() === 0) {
            DB::table('aca_schools')->insert([
                'name' => 'Mi Colegio',
                'is_default' => true,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('parameters')->where('parameter_code', 'PTM0005')->delete();
    }
};
