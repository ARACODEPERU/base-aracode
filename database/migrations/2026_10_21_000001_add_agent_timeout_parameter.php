<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Espera maxima del agente de OpenAI, expresada en minutos. */
    private const AGENT_TIMEOUT = 'IA-0000';

    public function up(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        $parameter = Parameter::firstOrNew(['parameter_code' => self::AGENT_TIMEOUT]);

        $parameter->fill([
            'description' => 'Tiempo maximo de espera de la respuesta del agente de OpenAI (Agents API), en minutos',
            'control_type' => 'tx',
            'json_query_data' => null,
        ]);

        if (!$parameter->exists) {
            $parameter->value_default = (string) max(1, (int) config('academic.openai.agent_timeout_minutes', 6));
        } elseif (trim((string) $parameter->value_default) === '') {
            // Un parametro creado a mano y vacio recupera el valor por defecto.
            $parameter->value_default = (string) max(1, (int) config('academic.openai.agent_timeout_minutes', 6));
        }

        $parameter->save();
    }

    public function down(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        Parameter::where('parameter_code', self::AGENT_TIMEOUT)->delete();
    }
};
