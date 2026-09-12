<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el parametro PHD0001: cuota de almacenamiento del sistema en GB.
     * Es editable desde la pantalla de Parametros y es la UNICA fuente de la
     * capacidad contra la cual se compara el espacio usado en el indicador
     * del dashboard (no se usa el disco real del servidor).
     *
     * Si la instalacion ya tenia la cuota en el codigo anterior (P000032),
     * se renombra a PHD0001 conservando el valor ya configurado en produccion.
     *
     * Valor inicial cuando no existe nada previo: DISC_SPACE del .env si es
     * numerico; de lo contrario 10 GB.
     */
    public function up(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        // Ya existe con el codigo nuevo: nada que hacer.
        if (Parameter::where('parameter_code', 'PHD0001')->exists()) {
            return;
        }

        // Renombrado seguro desde el codigo anterior, conservando el valor
        // configurado en produccion (solo si es nuestra fila por descripcion).
        $previous = Parameter::where('parameter_code', 'P000032')->first();

        if ($previous !== null) {
            $previous->update(['parameter_code' => 'PHD0001']);

            return;
        }

        // Instalacion nueva: valor inicial desde .env o 10 GB.
        $initialQuota = trim((string) env('DISC_SPACE', ''));

        if ($initialQuota === '' || !is_numeric($initialQuota) || (float) $initialQuota <= 0) {
            $initialQuota = '10';
        }

        Parameter::create([
            'parameter_code'   => 'PHD0001',
            'description'      => 'Cuota de almacenamiento del sistema en GB (indicador del dashboard)',
            'control_type'     => 'tx',
            'json_query_data'  => null,
            'value_default'    => $initialQuota,
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        // Revertir solo la fila propia (por descripcion, para no borrar datos ajenos).
        Parameter::where('parameter_code', 'PHD0001')
            ->where('description', 'Cuota de almacenamiento del sistema en GB (indicador del dashboard)')
            ->delete();
    }
};
