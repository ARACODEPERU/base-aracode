<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** URL del servidor de SMSGate (la misma que configura la aplicacion movil). */
    private const URL_CODE = 'SC-00003';

    /** Usuario de la cuenta de SMSGate. */
    private const USER_CODE = 'SC-00004';

    /** Contrasena de la cuenta de SMSGate. */
    private const PASSWORD_CODE = 'SC-00005';

    /** URL por defecto: la que usa la aplicacion movil de SMSGate. */
    private const DEFAULT_URL = 'https://api.sms-gate.app/mobile/v1';

    /**
     * Crea los parametros que alimentan el canal SMSGate.
     *
     * El sistema envia por el API externo del servidor de SMSGate
     * (POST {servidor}/3rdparty/v1/messages) autenticandose con usuario y
     * contrasena. La aplicacion movil se conecta al mismo servidor por
     * /mobile/v1; guardamos esa URL (SC-00003) y se normaliza al path externo.
     *
     * Si falta el usuario o la contrasena, la opcion "SMS via SMSGate" no se
     * muestra en la pantalla de Notificaciones del modulo Academico.
     *
     * Idempotente: crea lo que falta y completa descripcion/valor vacios.
     */
    public function up(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        $this->createOrFill(
            self::URL_CODE,
            'SMSGate: URL del servidor (la misma que usa la aplicacion movil; por defecto ' . self::DEFAULT_URL . ')',
            self::DEFAULT_URL
        );

        $this->createOrFill(
            self::USER_CODE,
            'SMSGate: usuario (username) de la cuenta para enviar los SMS por el API externo',
            null
        );

        $this->createOrFill(
            self::PASSWORD_CODE,
            'SMSGate: contrasena (password) de la cuenta para enviar los SMS por el API externo',
            null
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        // Revertir solo las filas propias (por descripcion, para no borrar datos ajenos).
        Parameter::whereIn('parameter_code', [self::URL_CODE, self::USER_CODE, self::PASSWORD_CODE])
            ->where('description', 'like', 'SMSGate:%')
            ->delete();
    }

    /**
     * Crea el parametro; si ya existe, completa la descripcion/valor vacios y
     * reescribe la descripcion del modelo anterior (webhook pull) al nuevo.
     */
    private function createOrFill(string $code, string $description, ?string $default): void
    {
        $parameter = Parameter::where('parameter_code', $code)->first();

        if (! $parameter) {
            Parameter::create([
                'parameter_code'  => $code,
                'description'     => $description,
                'control_type'    => 'tx',
                'json_query_data' => null,
                'value_default'   => $default,
            ]);

            return;
        }

        $changes = [];

        // Nuestras descripciones anteriores empiezan con "SMSGate:"; se
        // reescriben porque el parametro cambio de significado.
        if (trim((string) $parameter->description) === '' || str_starts_with((string) $parameter->description, 'SMSGate:')) {
            $changes['description'] = $description;
        }

        if ($default !== null && trim((string) $parameter->value_default) === '') {
            $changes['value_default'] = $default;
        }

        // Valor del modelo pull anterior (URL del webhook del sistema): se
        // reemplaza por la URL del servidor de SMSGate.
        if ($code === self::URL_CODE && str_contains((string) $parameter->value_default, '/academic/smsgate/webhook')) {
            $changes['value_default'] = $default;
        }

        if ($changes !== []) {
            $parameter->update($changes);
        }
    }
};
