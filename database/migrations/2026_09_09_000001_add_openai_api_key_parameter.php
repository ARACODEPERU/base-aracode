<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea (si no existe) el parametro P000025 con la API Key de OpenAI
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

        $exists = Parameter::where('parameter_code', 'P000025')->exists();

        if ($exists) {
            return;
        }

        Parameter::create([
            'parameter_code' => 'P000025',
            'description' => 'API Key de OpenAI para consultas de IA del sistema',
            'control_type' => 'tx',
            'json_query_data' => null,
            'value_default' => env('API_KEY_IA', env('OPENAI_API_KEY')) ?: null,
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        Parameter::where('parameter_code', 'P000025')->delete();
    }
};
