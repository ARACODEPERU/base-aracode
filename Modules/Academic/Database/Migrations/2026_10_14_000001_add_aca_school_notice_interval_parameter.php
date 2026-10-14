<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Pausa mínima entre mensajes SMS del canal de asistencia (milisegundos). */
    private const INTERVAL_CODE = 'SC-00032';

    /** Valor por defecto: 2 segundos entre mensajes (anti-SPAM de los operadores). */
    private const DEFAULT_INTERVAL_MS = '2000';

    /**
     * Parámetro del intervalo mínimo entre mensajes de los avisos de
     * asistencia del colegio.
     *
     * Los operadores castigan el envío en ráfaga como SPAM: el job de envío
     * separa cada SMS al menos este tiempo (por defecto 2000 ms).
     *
     * Es idempotente: solo crea la fila si falta y no pisa un valor ya
     * cargado por el administrador.
     */
    public function up(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        $parameter = Parameter::firstOrCreate(
            ['parameter_code' => self::INTERVAL_CODE],
            [
                'description' => 'Avisos de asistencia (Colegio): pausa mínima entre cada mensaje SMS en milisegundos (anti-SPAM de los operadores)',
                'control_type' => 'tx',
                'json_query_data' => null,
                'value_default' => self::DEFAULT_INTERVAL_MS,
            ]
        );

        $current = trim((string) $parameter->description);

        if ($current === '' || str_starts_with($current, 'Avisos de asistencia (Colegio):')) {
            $expected = 'Avisos de asistencia (Colegio): pausa mínima entre cada mensaje SMS en milisegundos (anti-SPAM de los operadores)';

            if ($current !== $expected) {
                $parameter->update(['description' => $expected]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        // Revertir solo la fila propia (por código + descripción).
        Parameter::where('parameter_code', self::INTERVAL_CODE)
            ->where('description', 'like', 'Avisos de asistencia (Colegio):%')
            ->delete();
    }
};
