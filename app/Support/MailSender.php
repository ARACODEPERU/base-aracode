<?php

namespace App\Support;

use Illuminate\Mail\Mailables\Address;

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

    /**
     * Buzon de administracion usado solo si MAIL_ADMIN no trae un correo util.
     *
     * Es el buzon real de la empresa (el mismo que ya se usa en
     * CmsSubscriberController): el aviso tiene que llegar a alguien aunque el
     * .env este vacio o mal escrito.
     */
    public const FALLBACK_ADMIN_ADDRESS = 'jsuclupe@globalcpaperu.com';

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

    /**
     * Direccion de respuesta (Reply-To) de un correo.
     *
     * Varios mailables dejan que quien origina el mensaje reciba las respuestas;
     * esa direccion sale de datos de negocio (cliente, contacto, formulario) y
     * puede faltar o venir mal escrita. Si no es un correo valido se devuelve
     * null y el mensaje sale sin encabezado Reply-To, de modo que las respuestas
     * caen al remitente configurado en lugar de tumbar el envio.
     */
    public static function replyTo(?string $address, ?string $name = null): ?Address
    {
        $address = trim((string) $address);

        if (! filter_var($address, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $name = trim((string) $name);

        return new Address($address, $name !== '' ? $name : null);
    }

    /**
     * Correo de administracion (MAIL_ADMIN) listo para usarse como destinatario.
     *
     * Se lee de config() y no de env() por el mismo motivo que address(): con la
     * configuracion cacheada env() deja de leer el .env. Si la variable no esta
     * definida, viene vacia o no es un correo valido se devuelve el respaldo de la
     * empresa, de modo que la lista de destinatarios nunca quede incompleta.
     */
    public static function adminAddress(): string
    {
        $address = trim((string) config('services.email.admin_address'));

        return filter_var($address, FILTER_VALIDATE_EMAIL)
            ? $address
            : self::FALLBACK_ADMIN_ADDRESS;
    }
}
