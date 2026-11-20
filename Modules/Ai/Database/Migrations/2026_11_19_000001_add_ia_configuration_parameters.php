<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Parámetros del módulo de IA (IA-00005 a IA-00012).
 *
 * Son las mismas filas que muestra y edita la pantalla de Parámetros del
 * sistema: no se crea ninguna configuración paralela. Nunca se pisa un valor
 * que el administrador ya haya guardado.
 */
return new class extends Migration
{
    private const PARAMETERS = [
        'IA-00005' => [
            'description' => 'Modelo de OpenAI que responde las consultas de IA (por ejemplo gpt-4.1-mini)',
            'control_type' => 'tx',
            'json_query_data' => null,
            'value' => 'gpt-4.1-mini',
        ],
        'IA-00006' => [
            'description' => 'Modelo de Gemini que responde las consultas de IA (por ejemplo gemini-2.5-flash)',
            'control_type' => 'tx',
            'json_query_data' => null,
            'value' => 'gemini-2.5-flash',
        ],
        'IA-00007' => [
            'description' => 'Presupuesto mensual de IA en dólares para todo el sistema (0 = sin límite)',
            'control_type' => 'tx',
            'json_query_data' => null,
            'value' => '0',
        ],
        'IA-00008' => [
            'description' => 'Consultas de IA permitidas por usuario y día (0 = sin límite)',
            'control_type' => 'tx',
            'json_query_data' => null,
            'value' => '0',
        ],
        'IA-00009' => [
            'description' => 'Guardar el texto de las consultas de IA para auditoría (1 = sí, 0 = no)',
            'control_type' => 'chx',
            'json_query_data' => null,
            'value' => '0',
        ],
        'IA-00010' => [
            'description' => 'Máximo de tokens de salida por respuesta de IA',
            'control_type' => 'tx',
            'json_query_data' => null,
            'value' => '800',
        ],
        'IA-00011' => [
            'description' => 'Temperatura de las respuestas de IA (0 = precisa, 2 = creativa)',
            'control_type' => 'tx',
            'json_query_data' => null,
            'value' => '0.2',
        ],
        'IA-00012' => [
            'description' => 'API Key de Gemini para las consultas de IA del sistema',
            'control_type' => 'pwd',
            'json_query_data' => null,
            'value' => null,
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        foreach (self::PARAMETERS as $code => $attributes) {
            $parameter = Parameter::firstOrNew(['parameter_code' => $code]);

            $parameter->fill([
                'description' => $attributes['description'],
                'control_type' => $attributes['control_type'],
                'json_query_data' => $attributes['json_query_data'],
            ]);

            // Solo se asigna el valor inicial al crear la fila: un ajuste ya
            // cargado por el administrador nunca se sobrescribe.
            if (! $parameter->exists) {
                $parameter->value_default = $attributes['value'];
            }

            $parameter->save();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        Parameter::whereIn('parameter_code', array_keys(self::PARAMETERS))->delete();
    }
};
