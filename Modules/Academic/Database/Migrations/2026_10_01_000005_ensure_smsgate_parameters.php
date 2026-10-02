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

    private const URL_DESCRIPTION = 'SMSGate: URL del servidor (la misma que usa la aplicacion movil; por defecto ' . self::DEFAULT_URL . ')';

    private const USER_DESCRIPTION = 'SMSGate: usuario (username) de la cuenta para enviar los SMS por el API externo';

    private const PASSWORD_DESCRIPTION = 'SMSGate: contrasena (password) de la cuenta para enviar los SMS por el API externo';

    /**
     * Asegura los parametros que alimentan el canal SMSGate.
     *
     * El sistema envia por el API externo del servidor de SMSGate
     * (POST {servidor}/3rdparty/v1/messages) autenticandose con usuario y
     * contrasena. La aplicacion movil se conecta al mismo servidor por
     * /mobile/v1; guardamos esa URL (SC-00003) y SmsgateService la normaliza al
     * path externo.
     *
     * Esta migracion existe porque la version anterior de los parametros
     * (bearer + URL del webhook de este sistema) ya estaba registrada y no se
     * vuelve a ejecutar: aqui se crean los que falten, se corrigen las
     * descripciones viejas y se repara el reparto de valores sin pisar las
     * credenciales ya cargadas.
     *
     * Es idempotente: se puede correr varias veces sin efectos secundarios.
     */
    public function up(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        $url = $this->ensure(self::URL_CODE, self::URL_DESCRIPTION, self::DEFAULT_URL);
        $user = $this->ensure(self::USER_CODE, self::USER_DESCRIPTION, null);

        $this->ensure(self::PASSWORD_CODE, self::PASSWORD_DESCRIPTION, null);

        $urlValue = trim((string) $url->value_default);
        $userValue = trim((string) $user->value_default);

        // Reparar el reparto del modelo anterior: la URL del servidor pudo
        // quedar guardada en SC-00004 (usuario).
        if (! $this->looksLikeUrl($urlValue) && $this->looksLikeUrl($userValue)) {
            $url->update(['value_default' => $userValue]);
            $user->update(['value_default' => null]);

            $urlValue = $userValue;
            $userValue = '';
        }

        // SC-00003 debe ser una URL: si quedo vacio o con un valor que no lo es
        // (por ejemplo el bearer del modelo anterior), se deja el default.
        if (! $this->looksLikeUrl($urlValue)) {
            $url->update(['value_default' => self::DEFAULT_URL]);
        }

        // El usuario no puede ser una URL.
        if ($this->looksLikeUrl($userValue)) {
            $user->update(['value_default' => null]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        // Revertir solo las filas propias (por codigo + descripcion).
        Parameter::whereIn('parameter_code', [self::URL_CODE, self::USER_CODE, self::PASSWORD_CODE])
            ->where('description', 'like', 'SMSGate:%')
            ->delete();
    }

    /**
     * Crea el parametro si falta y corrige la descripcion cuando es nuestra
     * (vacia o "SMSGate:..."). No pisa un valor utilizable.
     */
    private function ensure(string $code, string $description, ?string $default): Parameter
    {
        $parameter = Parameter::firstOrCreate(
            ['parameter_code' => $code],
            [
                'description'     => $description,
                'control_type'    => 'tx',
                'json_query_data' => null,
                'value_default'   => $default,
            ]
        );

        $current = trim((string) $parameter->description);

        if (($current === '' || str_starts_with($current, 'SMSGate:')) && $current !== $description) {
            $parameter->update(['description' => $description]);
        }

        return $parameter;
    }

    /**
     * true si el valor parece una URL http(s).
     */
    private function looksLikeUrl(string $value): bool
    {
        return $value !== '' && preg_match('#^https?://#i', $value) === 1;
    }
};
