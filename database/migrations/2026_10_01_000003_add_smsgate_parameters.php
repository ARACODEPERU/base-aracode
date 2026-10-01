<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Bearer (secreto) que la app SMSGate envia al webhook del sistema. */
    private const BEARER_CODE = 'SC-00003';

    /** URL del webhook de este mismo sistema a la que la app SMSGate consulta. */
    private const WEBHOOK_CODE = 'SC-00004';

    /** Ruta publica del webhook de SMSGate dentro de este sistema. */
    private const WEBHOOK_PATH = '/api/academic/smsgate/webhook';

    /**
     * Crea los parametros que alimentan el canal SMSGate.
     *
     * SC-00003 guarda el bearer (secreto) que la aplicacion SMSGate debe enviar
     * en el encabezado Authorization al consultar el webhook del sistema; si el
     * parametro esta vacio, la opcion "SMS via SMSGate" no se muestra en la
     * pantalla de Notificaciones del modulo Academico.
     *
     * SC-00004 guarda la URL del webhook de este sistema (la misma a la que
     * apunta la app), analogo al webhook del bot de Telegram. Al crearse se
     * precarga con la URL publica derivada de config('app.url') y puede
     * sobrescribirse.
     *
     * Idempotente: si la fila ya existe no se toca el valor (asi un
     * administrador conserva lo que configuro); solo se completa la descripcion
     * o la URL cuando estaban vacias.
     */
    public function up(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        $this->createOrFill(
            self::BEARER_CODE,
            'SMSGate: bearer (secreto) que la aplicacion envia al webhook del sistema para las notificaciones por SMS',
            null
        );

        $this->createOrFill(
            self::WEBHOOK_CODE,
            'SMSGate: URL del webhook de este sistema a la que la aplicacion consulta los mensajes pendientes (por defecto '
                . $this->defaultWebhookUrl() . ')',
            $this->defaultWebhookUrl()
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        // Revertir solo las filas propias (por descripcion, para no borrar datos ajenos).
        Parameter::where('parameter_code', self::BEARER_CODE)
            ->where('description', 'like', 'SMSGate:%')
            ->delete();

        Parameter::where('parameter_code', self::WEBHOOK_CODE)
            ->where('description', 'like', 'SMSGate:%')
            ->delete();
    }

    /**
     * Crea el parametro; si ya existe, solo rellena la descripcion o el valor
     * cuando estaban vacios.
     */
    private function createOrFill(string $code, string $description, ?string $default): void
    {
        $parameter = Parameter::where('parameter_code', $code)->first();

        if ($parameter) {
            $changes = [];

            if (trim((string) $parameter->description) === '') {
                $changes['description'] = $description;
            }

            if ($default !== null && trim((string) $parameter->value_default) === '') {
                $changes['value_default'] = $default;
            }

            if ($changes !== []) {
                $parameter->update($changes);
            }

            return;
        }

        Parameter::create([
            'parameter_code'  => $code,
            'description'     => $description,
            'control_type'    => 'tx',
            'json_query_data' => null,
            'value_default'   => $default,
        ]);
    }

    /**
     * URL publica por defecto del webhook de SMSGate.
     */
    private function defaultWebhookUrl(): string
    {
        return rtrim((string) config('app.url'), '/') . self::WEBHOOK_PATH;
    }
};
