<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Espera maxima del agente de OpenAI, expresada en minutos.
     *
     * Migracion historica ya ejecutada en produccion: el codigo correcto de la
     * secuencia es IA-00004 y lo aplica 2026_10_21_000002
     * (fix_agent_timeout_parameter_code). El codigo de la migracion se mantiene
     * como se ejecuto en produccion; solo el valor inicial es fijo (6 minutos).
     */
    private const AGENT_TIMEOUT = 'IA-0000';

    /** Minutos de espera iniciales: la espera no se configura por .env. */
    private const DEFAULT_TIMEOUT_MINUTES = 6;

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
            $parameter->value_default = (string) self::DEFAULT_TIMEOUT_MINUTES;
        } elseif (trim((string) $parameter->value_default) === '') {
            // Un parametro creado a mano y vacio recupera el valor por defecto.
            $parameter->value_default = (string) self::DEFAULT_TIMEOUT_MINUTES;
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
