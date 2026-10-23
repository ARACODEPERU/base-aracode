<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Codigo publicado por error en la migracion 2026_10_21_000001. No sigue la
     * secuencia de los parametros de IA (IA-00001..IA-00003) y ya se ejecuto en
     * produccion, por eso se corrige aqui en lugar de editar esa migracion.
     */
    private const LEGACY_CODE = 'IA-0000';

    /** Siguiente codigo libre de la secuencia de parametros de IA. */
    private const CURRENT_CODE = 'IA-00004';

    private const DESCRIPTION = 'Tiempo maximo de espera de la respuesta del agente de OpenAI (Agents API), en minutos';

    /** Espera inicial cuando el parametro todavia no existe o esta vacio. */
    private const DEFAULT_TIMEOUT_MINUTES = 6;

    /**
     * Deja siempre un unico parametro con el codigo correcto, sin perder el
     * valor que el administrador haya configurado:
     *
     * - IA-0000 existe y IA-00004 no: se renombra conservando el valor.
     * - Ya existe IA-00004: se reutiliza y se elimina el codigo viejo duplicado.
     * - No existe ninguno: se crea con la espera por defecto (6 minutos).
     *
     * Se puede ejecutar varias veces sin efectos secundarios.
     */
    public function up(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        $defaultValue = (string) self::DEFAULT_TIMEOUT_MINUTES;
        $legacy = Parameter::where('parameter_code', self::LEGACY_CODE)->first();
        $current = Parameter::where('parameter_code', self::CURRENT_CODE)->first();

        if ($current) {
            if (trim((string) $current->value_default) === '') {
                $legacyValue = $legacy ? trim((string) $legacy->value_default) : '';

                $current->value_default = $legacyValue !== '' ? $legacyValue : $defaultValue;
                $current->save();
            }

            // El codigo viejo quedo obsoleto: se elimina para no tener dos
            // parametros controlando la misma espera.
            if ($legacy) {
                $legacy->delete();
            }

            return;
        }

        if ($legacy) {
            $legacy->fill([
                'parameter_code' => self::CURRENT_CODE,
                'description' => self::DESCRIPTION,
                'control_type' => 'tx',
                'json_query_data' => null,
            ]);

            if (trim((string) $legacy->value_default) === '') {
                $legacy->value_default = $defaultValue;
            }

            $legacy->save();

            return;
        }

        Parameter::create([
            'parameter_code' => self::CURRENT_CODE,
            'description' => self::DESCRIPTION,
            'control_type' => 'tx',
            'json_query_data' => null,
            'value_default' => $defaultValue,
        ]);
    }

    /**
     * Devuelve el parametro al codigo que espera la migracion anterior, para
     * que un rollback completo deje la base como estaba antes de esta
     * correccion. Si el codigo viejo ya existe (por ejemplo, alguien lo creo a
     * mano), no se toca nada.
     */
    public function down(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        $current = Parameter::where('parameter_code', self::CURRENT_CODE)->first();

        if (!$current || Parameter::where('parameter_code', self::LEGACY_CODE)->exists()) {
            return;
        }

        // Solo se revierte el parametro de espera del agente, nunca otro
        // parametro que ocupe el codigo IA-00004.
        if (!str_contains(mb_strtolower((string) $current->description), 'agente')) {
            return;
        }

        $current->parameter_code = self::LEGACY_CODE;
        $current->save();
    }
};
