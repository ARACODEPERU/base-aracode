<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** URL del servidor de SMSGate para los avisos de citas de Salud. */
    private const URL_CODE = 'SC-00006';

    /** Usuario de la cuenta de SMSGate de Salud. */
    private const USER_CODE = 'SC-00007';

    /** Contrasena de la cuenta de SMSGate de Salud. */
    private const PASSWORD_CODE = 'SC-00008';

    /** Interruptor que habilita el canal SMS Gateway de los avisos de citas. */
    private const ENABLED_CODE = 'SC-00009';

    /** URL por defecto: la que usa la aplicacion movil de SMSGate. */
    private const DEFAULT_URL = 'https://api.sms-gate.app/mobile/v1';

    private const URL_DESCRIPTION = 'SMSGate (Salud): URL del servidor para los avisos de citas (la misma que usa la aplicacion movil; por defecto ' . self::DEFAULT_URL . ')';

    private const USER_DESCRIPTION = 'SMSGate (Salud): usuario (username) de la cuenta para enviar los avisos de citas por el API externo';

    private const PASSWORD_DESCRIPTION = 'SMSGate (Salud): contrasena (password) de la cuenta para enviar los avisos de citas por el API externo';

    private const ENABLED_DESCRIPTION = 'Avisos de citas (Salud): activar el envio por SMS Gateway (solo se envia cuando este encendido)';

    /**
     * Parametros propios del modulo Salud para los recordatorios de citas.
     *
     * Continua la numeracion del Sistema (SC-00005 era el ultimo). El modulo
     * Salud no comparte las credenciales del Academico: la URL, el usuario y la
     * contrasena de SMSGate son propias (SC-00006, SC-00007 y SC-00008) y
     * SC-00009 es el interruptor Activo, que solo enciende el canal cuando esta
     * en '1'. Si falta cualquiera de los tres o el interruptor esta apagado, no
     * se envia ningun aviso.
     *
     * Es idempotente: solo crea las filas que falten y no pisa un valor ya
     * cargado por el administrador.
     */
    public function up(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        $this->ensure(self::URL_CODE, self::URL_DESCRIPTION, 'tx', self::DEFAULT_URL);
        $this->ensure(self::USER_CODE, self::USER_DESCRIPTION, 'tx', null);
        $this->ensure(self::PASSWORD_CODE, self::PASSWORD_DESCRIPTION, 'tx', null);
        $this->ensure(self::ENABLED_CODE, self::ENABLED_DESCRIPTION, 'chx', '0');
    }

    public function down(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        // Revertir solo las filas propias (por codigo + descripcion).
        Parameter::whereIn('parameter_code', [
            self::URL_CODE,
            self::USER_CODE,
            self::PASSWORD_CODE,
            self::ENABLED_CODE,
        ])->where(function ($query) {
            $query->where('description', 'like', 'SMSGate (Salud):%')
                ->orWhere('description', 'like', 'Avisos de citas (Salud):%');
        })->delete();
    }

    /**
     * Crea el parametro si falta. Si ya existe solo completa la descripcion
     * cuando quedo vacia o es una de las nuestras.
     */
    private function ensure(string $code, string $description, string $controlType, ?string $default): void
    {
        $parameter = Parameter::firstOrCreate(
            ['parameter_code' => $code],
            [
                'description'     => $description,
                'control_type'    => $controlType,
                'json_query_data' => null,
                'value_default'   => $default,
            ]
        );

        $current = trim((string) $parameter->description);

        if (($current === '' || str_starts_with($current, 'SMSGate (Salud):') || str_starts_with($current, 'Avisos de citas (Salud):')) && $current !== $description) {
            $parameter->update(['description' => $description]);
        }
    }
};
