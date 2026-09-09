<?php

namespace Database\Seeders;

use App\Models\Parameter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ParameterOpenAiSeeder extends Seeder
{
    /**
     * Crea (si no existe) el parametro P000025 con la API Key de OpenAI
     * usada por el chat y la censura de texto del sistema.
     *
     * - Nunca sobrescribe una key ya guardada en la base de datos.
     * - Solo copia API_KEY_IA del .env si el parametro esta vacio (primera vez).
     */
    public function run(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        $parameter = Parameter::firstOrNew([
            'parameter_code' => 'P000025',
        ]);

        if (!$parameter->exists) {
            $parameter->fill([
                'description' => 'API Key de OpenAI para consultas de IA del sistema',
                'control_type' => 'tx',
                'json_query_data' => null,
            ]);

            $envKey = trim((string) env('API_KEY_IA', env('OPENAI_API_KEY')));

            $parameter->value_default = $envKey !== '' ? $envKey : null;

            $parameter->save();
        }
    }
}
