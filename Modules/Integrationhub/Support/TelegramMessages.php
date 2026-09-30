<?php

namespace Modules\Integrationhub\Support;

use RuntimeException;

/**
 * Catalogo de los textos que envia el bot de Telegram.
 *
 * Aqui viven el nombre, la explicacion, las variables disponibles y el texto de
 * fabrica de cada mensaje. Es la unica fuente de verdad de los valores por
 * defecto: la tabla integration_telegram_messages solo guarda los reemplazos,
 * de modo que "restaurar" siempre tiene a donde volver y agregar un mensaje
 * nuevo no requiere migracion.
 *
 * Las variables se escriben entre llaves ({nombre}) y una linea que depende de
 * una variable vacia no se envia: asi, por ejemplo, el bloque de programas
 * desaparece solo cuando la persona se registro por suscripcion.
 */
class TelegramMessages
{
    /** El texto interpreta las etiquetas de Telegram (<b>, <i>, ...). */
    public const FORMAT_HTML = 'html';

    /** El texto se envia tal cual: las etiquetas se ven escritas. */
    public const FORMAT_TEXT = 'text';

    /** Primer mensaje al abrir el bot: pide el documento. */
    public const START_NEW = 'bot_start_new';

    /** Primer mensaje cuando el chat ya estaba registrado. */
    public const START_REGISTERED = 'bot_start_registered';

    /** El texto recibido no parece un numero de documento. */
    public const DOCUMENT_UNRECOGNIZED = 'bot_document_unrecognized';

    /** El documento no esta en el padron. */
    public const DOCUMENT_NOT_FOUND = 'bot_document_not_found';

    /** El documento pertenece a alguien sin programa ni suscripcion. */
    public const DOCUMENT_NO_PROGRAMS = 'bot_document_no_programs';

    /** Se agotaron los intentos. */
    public const TOO_MANY_ATTEMPTS = 'bot_too_many_attempts';

    /** Registro confirmado. */
    public const REGISTERED = 'bot_registered';

    /** El padron no esta disponible para validar documentos. */
    public const REGISTRATION_DISABLED = 'bot_registration_disabled';

    /** Baja confirmada. */
    public const OPTOUT_DONE = 'bot_optout_done';

    /** Baja de un chat que no estaba registrado. */
    public const OPTOUT_NONE = 'bot_optout_none';

    /** Recordatorio para un mensaje suelto sin /start. */
    public const START_HINT = 'bot_start_hint';

    /** Ayuda (/ayuda). */
    public const HELP = 'bot_help';

    /** Plantilla con la que se arma el mensaje de una campana de Telegram. */
    public const CAMPAIGN = 'campaign_telegram';

    /**
     * Definicion de cada mensaje, en el orden en que se muestran en pantalla.
     *
     * @return array<string, array{name: string, description: string, variables: array<int, string>, format: string, body: string}>
     */
    public static function all(): array
    {
        return [
            self::START_NEW => [
                'name' => 'Pedido del documento (/start)',
                'description' => 'Primer mensaje cuando alguien abre el bot o reinicia el registro.',
                'variables' => [],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                👋 ¡Hola! Soy el bot de avisos de la institución.

                Para activar los avisos en este chat, escríbeme tu <b>número de documento (DNI)</b>, solo el número.

                Si ya no quieres recibir avisos, escribe /baja.
                TXT,
            ],
            self::START_REGISTERED => [
                'name' => 'Pedido del documento (chat ya registrado)',
                'description' => 'Se envia en lugar del anterior cuando ese chat ya figura como registrado.',
                'variables' => ['{nombre}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                👋 Hola {nombre}, este chat ya está registrado.

                Si quieres asociarlo a otro documento, escríbelo aquí.

                Si ya no quieres recibir avisos, escribe /baja.
                TXT,
            ],
            self::DOCUMENT_UNRECOGNIZED => [
                'name' => 'Documento no reconocido',
                'description' => 'El mensaje no parece un número de documento (letras, frases, texto muy corto).',
                'variables' => ['{intentos}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                🤔 No reconocí eso como un número de documento.

                Escríbeme solo el número, sin espacios ni guiones. ({intentos})
                TXT,
            ],
            self::DOCUMENT_NOT_FOUND => [
                'name' => 'Documento no encontrado',
                'description' => 'El documento no pertenece a nadie del padrón.',
                'variables' => ['{intentos}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                🔎 No encontramos ese documento en el padrón.

                Verifícalo y vuelve a escribirlo, o pide ayuda a la institución. ({intentos})
                TXT,
            ],
            self::DOCUMENT_NO_PROGRAMS => [
                'name' => 'Sin programa ni suscripción',
                'description' => 'La persona existe, pero no tiene matrícula activa ni suscripción vigente.',
                'variables' => [],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                😕 Encontramos tu ficha, pero no tiene un programa activo ni una suscripción vigente.

                Contacta a la institución para revisar tu matrícula.
                TXT,
            ],
            self::TOO_MANY_ATTEMPTS => [
                'name' => 'Demasiados intentos',
                'description' => 'Se agotaron los intentos seguidos y el bot deja de pedir el documento.',
                'variables' => [],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                🚫 Demasiados intentos seguidos.

                Verifica tu documento y escribe /start para volver a intentarlo.
                TXT,
            ],
            self::REGISTERED => [
                'name' => 'Registro confirmado',
                'description' => 'Cierre del registro. {programas} se reemplaza por los programas del alumno y {suscripcion} por "Suscripción activa" cuando corresponde; si vienen vacíos, su línea no se envía.',
                'variables' => ['{nombre}', '{programas}', '{suscripcion}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                ✅ ¡Listo, {nombre}! Tu chat quedó registrado y desde ahora recibirás por aquí los avisos de la institución.

                📚 Programas: {programas}

                💳 {suscripcion}

                Si quieres dejar de recibirlos, escribe /baja.
                TXT,
            ],
            self::REGISTRATION_DISABLED => [
                'name' => 'Registro no disponible',
                'description' => 'No hay padrón vinculado para validar el documento (módulo no disponible).',
                'variables' => [],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                ⚠️ El registro por Telegram no está habilitado en este momento.

                Contacta a la institución.
                TXT,
            ],
            self::OPTOUT_DONE => [
                'name' => 'Baja confirmada',
                'description' => 'Respuesta a /baja cuando el chat estaba registrado.',
                'variables' => [],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                👍 Listo, ya no recibirás avisos por este chat.

                Si quieres volver a activarlo, escribe /start y tu número de documento.
                TXT,
            ],
            self::OPTOUT_NONE => [
                'name' => 'Baja sin registro',
                'description' => 'Respuesta a /baja cuando ese chat no estaba registrado.',
                'variables' => [],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                ℹ️ Este chat no estaba registrado, así que no había nada que dar de baja.
                TXT,
            ],
            self::START_HINT => [
                'name' => 'Recordatorio (sin /start)',
                'description' => 'Respuesta corta a cualquier mensaje cuando no hay un registro en curso.',
                'variables' => [],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                👉 Para activar los avisos en este chat escribe /start y luego tu número de documento (DNI).
                TXT,
            ],
            self::HELP => [
                'name' => 'Ayuda (/ayuda)',
                'description' => 'Explica cómo registrarse y cómo darse de baja.',
                'variables' => [],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                ℹ️ Soy el bot de avisos de la institución.

                Para activar los avisos en este chat: escribe /start y luego tu número de documento (DNI).

                Para dejar de recibir avisos: /baja.
                TXT,
            ],
            self::CAMPAIGN => [
                'name' => 'Plantilla de las campañas de Telegram',
                'description' => 'Cómo se arma el aviso masivo: {mensaje} es lo que se escribe en la pantalla de Notificaciones y {curso} y {tiempo} salen de ese mismo formulario. Aplica solo al canal Telegram (el SMS conserva su formato).',
                'variables' => ['{mensaje}', '{curso}', '{tiempo}', '{nombre}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                👋 Hola {nombre}:

                {mensaje}

                📚 Curso: {curso}
                ⏰ Tiempo: {tiempo}
                TXT,
            ],
        ];
    }

    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function has(string $code): bool
    {
        return array_key_exists($code, self::all());
    }

    /**
     * @return array{name: string, description: string, variables: array<int, string>, format: string, body: string}|null
     */
    public static function definition(string $code): ?array
    {
        return self::all()[$code] ?? null;
    }

    /**
     * Formato normalizado: null significa "el del catalogo".
     */
    public static function normalizeFormat(?string $format): ?string
    {
        $format = strtolower(trim((string) $format));

        if ($format === '') {
            return null;
        }

        if (! in_array($format, [self::FORMAT_HTML, self::FORMAT_TEXT], true)) {
            throw new RuntimeException("El formato '{$format}' no es válido para un mensaje de Telegram.");
        }

        return $format;
    }
}
