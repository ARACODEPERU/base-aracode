<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el parametro PTM0004: interruptor maestro multi-moneda del sistema.
     *
     * value_default = '0' (desactivado): todo el sistema opera solo en soles;
     * el combo de moneda de los puntos de venta no se muestra.
     * value_default = '1' (activado): se puede vender en soles y dolares
     * (el combo de moneda se muestra y el TC se consulta a SUNAT via Migo).
     *
     * Usa el prefijo PTM para no chocar con codigos P ya existentes en produccion.
     * Idempotente: si el parametro ya existe, solo actualiza su descripcion.
     */
    public function up(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        $description = 'Trabajo con multiples monedas en ventas (0 = solo soles, 1 = soles y dolares)';

        $parameter = Parameter::where('parameter_code', 'PTM0004')->first();

        if ($parameter) {
            $parameter->update(['description' => $description]);

            return;
        }

        Parameter::create([
            'parameter_code'  => 'PTM0004',
            'description'     => $description,
            'control_type'    => 'chx',
            'json_query_data' => null,
            'value_default'   => '0',
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        // Revertir solo la fila propia (por descripcion, para no borrar datos ajenos).
        Parameter::where('parameter_code', 'PTM0004')
            ->where('description', 'Trabajo con multiples monedas en ventas (0 = solo soles, 1 = soles y dolares)')
            ->delete();
    }
};
