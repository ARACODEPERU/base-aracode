<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el parametro PN00001 con la lista de correos (separados por coma)
     * que recibiran la notificacion cuando un cliente confirma una negociacion
     * (administradores y vendedores).
     *
     * Usa el prefijo PN para no chocar con codigos P ya existentes en produccion.
     *
     * Idempotente: si el parametro ya existe, solo actualiza su descripcion.
     */
    public function up(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        $description = 'Correos de notificacion al confirmar una negociacion (separados por coma)';

        $parameter = Parameter::where('parameter_code', 'PN00001')->first();

        if ($parameter) {
            $parameter->update(['description' => $description]);

            return;
        }

        Parameter::create([
            'parameter_code'  => 'PN00001',
            'description'     => $description,
            'control_type'    => 'tx',
            'json_query_data' => null,
            'value_default'   => null,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        // Revertir solo la fila propia (por descripcion, para no borrar datos ajenos).
        Parameter::where('parameter_code', 'PN00001')
            ->where('description', 'Correos de notificacion al confirmar una negociacion (separados por coma)')
            ->delete();
    }
};
