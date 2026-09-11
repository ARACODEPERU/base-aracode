<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el parametro del sistema P000032 que contiene el codigo HTML/iframe
     * que se muestra en la vista "Ofertas Laborales".
     *
     * El enlace del sidebar y la vista solo estan disponibles para alumnos que
     * hayan comprado algun curso con precio mayor a 0 o que tengan una
     * suscripcion activa y vigente.
     *
     * Idempotente: si el parametro ya existe, solo se actualiza su descripcion.
     */
    public function up(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        $description = 'Codigo HTML/iframe de la vista Ofertas Laborales (visible solo con curso de pago o suscripcion activa)';

        $parameter = Parameter::where('parameter_code', 'P000032')->first();

        if ($parameter) {
            $parameter->update(['description' => $description]);

            return;
        }

        Parameter::create([
            'parameter_code'  => 'P000032',
            'description'     => $description,
            'control_type'    => 'tx',
            'json_query_data' => null,
            'value_default'   => null,
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        Parameter::where('parameter_code', 'P000032')->delete();
    }
};
