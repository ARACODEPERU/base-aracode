<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Parametro que guarda el token del bot de Telegram (BotFather). */
    private const PARAMETER_CODE = 'SC-00002';

    /** Descripcion visible en Parametros del sistema. */
    private const DESCRIPTION = 'Telegram: token del bot (BotFather) para el registro de chat_id y las notificaciones';

    /**
     * Crea el parametro que alimenta el canal de Telegram.
     *
     * El valor es el token que entrega BotFather (formato "123456789:AA...-x").
     * Si el parametro esta vacio, la opcion "Telegram" no se muestra en la
     * pantalla de Notificaciones del modulo Academico y tampoco se ofrece el
     * registro de chat_id.
     *
     * Idempotente: si la fila ya existe no se toca (asi un administrador que
     * creo el parametro a mano conserva su descripcion y su token). Solo se
     * completa la descripcion cuando la fila existia pero estaba vacia.
     */
    public function up(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        $parameter = Parameter::where('parameter_code', self::PARAMETER_CODE)->first();

        if ($parameter) {
            if (trim((string) $parameter->description) === '') {
                $parameter->update(['description' => self::DESCRIPTION]);
            }

            return;
        }

        Parameter::create([
            'parameter_code'  => self::PARAMETER_CODE,
            'description'     => self::DESCRIPTION,
            'control_type'    => 'tx',
            'json_query_data' => null,
            'value_default'   => null,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        // Revertir solo la fila propia (por descripcion, para no borrar datos ajenos).
        Parameter::where('parameter_code', self::PARAMETER_CODE)
            ->where('description', self::DESCRIPTION)
            ->delete();
    }
};
