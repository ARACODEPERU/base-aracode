<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** Interruptor que habilita la sincronizacion con Google Calendar. */
    private const ENABLED_CODE = 'SC-00010';

    /** Client ID de la aplicacion OAuth 2.0 de Google. */
    private const CLIENT_ID_CODE = 'SC-00011';

    /** Client Secret de la aplicacion OAuth 2.0 de Google. */
    private const CLIENT_SECRET_CODE = 'SC-00012';

    /** Refresh token de la cuenta de Google del consultorio. */
    private const REFRESH_TOKEN_CODE = 'SC-00013';

    /** Calendario del consultorio con el que se sincroniza. */
    private const CALENDAR_ID_CODE = 'SC-00014';

    /** Secreto que valida las notificaciones push (X-Goog-Channel-Token). */
    private const CHANNEL_TOKEN_CODE = 'SC-00015';

    /** Dias de ventana de la primera sincronizacion. */
    private const WINDOW_DAYS_CODE = 'SC-00016';

    /** Interruptor de la direccion Google -> sistema. */
    private const INBOUND_CODE = 'SC-00017';

    private const PREFIX = 'Google Calendar (Salud): ';

    private const ENABLED_DESCRIPTION = self::PREFIX . 'activar la sincronizacion con la Agenda (solo sincroniza cuando este encendido)';

    private const CLIENT_ID_DESCRIPTION = self::PREFIX . 'Client ID de OAuth 2.0 (Google Cloud Console > APIs y servicios > Credenciales). Confidencial: se registra una vez y no se vuelve a mostrar';

    private const CLIENT_SECRET_DESCRIPTION = self::PREFIX . 'Client Secret de OAuth 2.0 de la misma credencial. Confidencial: se registra una vez y no se vuelve a mostrar';

    private const REFRESH_TOKEN_DESCRIPTION = self::PREFIX . 'refresh token de OAuth 2.0 (lo guarda el boton "Conectar con Google"). Confidencial: no se muestra por seguridad';

    private const CALENDAR_ID_DESCRIPTION = self::PREFIX . 'identificador del calendario del consultorio (por defecto primary)';

    private const CHANNEL_TOKEN_DESCRIPTION = self::PREFIX . 'secreto del canal de notificaciones push; valida el encabezado X-Goog-Channel-Token. Confidencial: no se muestra por seguridad';

    private const WINDOW_DAYS_DESCRIPTION = self::PREFIX . 'dias de ventana de la primera sincronizacion (hacia atras y hacia adelante; por defecto 60)';

    private const INBOUND_DESCRIPTION = self::PREFIX . 'recibir en el sistema los cambios hechos en Google Calendar (apagado = la Agenda solo envia hacia Google)';

    private const DEFAULT_CALENDAR_ID = 'primary';

    private const DEFAULT_WINDOW_DAYS = '60';

    /**
     * Parametros propios del modulo Salud para la sincronizacion con Google
     * Calendar.
     *
     * Continua la numeracion del Sistema (SC-00009 era el ultimo, de los avisos
     * por SMS). El canal se usa solo cuando el interruptor Activo (SC-00010) esta
     * en '1' y existen Client ID, Client Secret y refresh token (SC-00011,
     * SC-00012 y SC-00013). El secreto del canal de push (SC-00015) se genera
     * aqui para que el webhook nazca protegido y nadie tenga que inventarlo.
     *
     * Las credenciales nacen con el tipo 'pwd' (confidencial): se registran una
     * sola vez y la pantalla de Parametros del sistema ya no vuelve a mostrarlas.
     * Al administrador solo le queda pulsar el boton "Conectar con Google".
     *
     * Es idempotente: solo crea las filas que falten y no pisa un valor ya
     * cargado por el administrador.
     */
    public function up(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        $this->ensure(self::ENABLED_CODE, self::ENABLED_DESCRIPTION, 'chx', '0');
        $this->ensure(self::CLIENT_ID_CODE, self::CLIENT_ID_DESCRIPTION, 'pwd', null);
        $this->ensure(self::CLIENT_SECRET_CODE, self::CLIENT_SECRET_DESCRIPTION, 'pwd', null);
        $this->ensure(self::REFRESH_TOKEN_CODE, self::REFRESH_TOKEN_DESCRIPTION, 'pwd', null);
        $this->ensure(self::CALENDAR_ID_CODE, self::CALENDAR_ID_DESCRIPTION, 'tx', self::DEFAULT_CALENDAR_ID);
        $this->ensure(self::CHANNEL_TOKEN_CODE, self::CHANNEL_TOKEN_DESCRIPTION, 'pwd', Str::random(40));
        $this->ensure(self::WINDOW_DAYS_CODE, self::WINDOW_DAYS_DESCRIPTION, 'tx', self::DEFAULT_WINDOW_DAYS);
        $this->ensure(self::INBOUND_CODE, self::INBOUND_DESCRIPTION, 'chx', '1');
    }

    public function down(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        // Revertir solo las filas propias (por codigo + descripcion).
        Parameter::whereIn('parameter_code', [
            self::ENABLED_CODE,
            self::CLIENT_ID_CODE,
            self::CLIENT_SECRET_CODE,
            self::REFRESH_TOKEN_CODE,
            self::CALENDAR_ID_CODE,
            self::CHANNEL_TOKEN_CODE,
            self::WINDOW_DAYS_CODE,
            self::INBOUND_CODE,
        ])->where('description', 'like', self::PREFIX . '%')->delete();
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

        if (($current === '' || str_starts_with($current, self::PREFIX)) && $current !== $description) {
            $parameter->update(['description' => $description]);
        }
    }
};
