<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea (si no existe) el parametro IA-00001 con la API Key de OpenAI
     * usada por el chat del sistema y la censura de texto.
     *
     * Idempotente: si el parametro ya existe en la base de datos, no se toca
     * nada (nunca sobrescribe una key ya guardada).
     */
    public function up(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        $exists = Parameter::where('parameter_code', 'IA-00001')->exists();

        if ($exists) {
            return;
        }

        Parameter::create([
            'parameter_code' => 'IA-00001',
            'description' => 'API Key de OpenAI para consultas de IA del sistema',
            // Confidencial: la pantalla de Parametros no vuelve a mostrar el valor.
            'control_type' => 'pwd',
            'json_query_data' => null,
            'value_default' => config('academic.openai.api_key') ?: null,
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        Parameter::where('parameter_code', 'IA-00001')->delete();
    }
};
