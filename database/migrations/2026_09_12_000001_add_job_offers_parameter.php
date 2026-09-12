<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el parametro del sistema PC00001 que contiene el codigo HTML/iframe
     * que se muestra en la vista "Ofertas Laborales".
     *
     * Usa un codigo propio para no compartirlo con P000032 (parametro que ya se
     * usa en el sidebar de alumnos): editar uno ya no afecta al otro.
     *
     * Idempotente: si el parametro ya existe, solo se actualiza su descripcion.
     */
    public function up(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        $description = 'Codigo HTML/iframe de la vista Ofertas Laborales (visible solo con curso de pago o suscripcion activa)';

        $parameter = Parameter::where('parameter_code', 'PC00001')->first();

        if ($parameter) {
            $parameter->update(['description' => $description]);

            return;
        }

        Parameter::create([
            'parameter_code'  => 'PC00001',
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

        // Revertir solo la fila propia (por descripcion, para no borrar datos ajenos).
        Parameter::where('parameter_code', 'PC00001')
            ->where('description', 'Codigo HTML/iframe de la vista Ofertas Laborales (visible solo con curso de pago o suscripcion activa)')
            ->delete();
    }
};
