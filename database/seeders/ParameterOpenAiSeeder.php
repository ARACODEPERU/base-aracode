<?php

namespace Database\Seeders;

use App\Models\Parameter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ParameterOpenAiSeeder extends Seeder
{
    /**
     * Crea (si no existe) el parametro IA-00001 con la API Key de OpenAI
     * usada por el chat y la censura de texto del sistema.
     *
     * - Nunca sobrescribe una key ya guardada en la base de datos.
     * - Solo copia API_KEY_IA (u OPENAI_API_KEY) del .env si el parametro esta
     *   vacio (primera vez).
     */
    public function run(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        $parameter = Parameter::firstOrNew([
            'parameter_code' => 'IA-00001',
        ]);

        if (!$parameter->exists) {
            $parameter->fill([
                'description' => 'API Key de OpenAI para consultas de IA del sistema',
                // Confidencial: la pantalla de Parametros no vuelve a mostrar el valor.
                'control_type' => 'pwd',
                'json_query_data' => null,
            ]);

            $envKey = trim((string) config('academic.openai.api_key'));

            $parameter->value_default = $envKey !== '' ? $envKey : null;

            $parameter->save();
        }
    }
}
