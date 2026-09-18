<?php

namespace App\Support;

/**
 * Remitente de los correos de la aplicacion.
 *
 * Se lee de config() y no de env(): cuando el servidor tiene la configuracion
 * cacheada (php artisan optimize / config:cache) Laravel deja de leer el .env y
 * env() devuelve siempre el valor por defecto, asi que cada correo terminaba
 * saliendo desde la direccion escrita a mano dentro de su propio codigo (distinta
 * en cada correo) en lugar de la configurada. config() conserva el valor real del
 * .env, con cache y sin cache.
 */
class MailSender
{
    /** Direccion usada solo si el .env no define una direccion util. */
    public const FALLBACK_ADDRESS = 'informes@globalcpaperu.com';

    public const FALLBACK_NAME = 'CPA Academy';

    public static function address(?string $fallback = null): string
    {
        $address = trim((string) config('mail.from.address'));

        // 'hello@example.com' es la direccion de ejemplo que trae Laravel: si el
        // .env no la cambio, no sirve como remitente real.
        if ($address === '' || str_ends_with(strtolower($address), '@example.com')) {
            return $fallback ?: self::FALLBACK_ADDRESS;
        }

        return $address;
    }

    public static function name(): string
    {
        return trim((string) config('mail.from.name')) ?: self::FALLBACK_NAME;
    }
}
