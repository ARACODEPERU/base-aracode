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

    /** Bloque con lo que el bot puede hacer (se incluye en el saludo). */
    public const MENU = 'bot_menu';

    /** Ayuda (/ayuda). */
    public const HELP = 'bot_help';

    /** Identificador del chat (/chatid). */
    public const CHAT_ID = 'bot_chat_id';

    /** La consulta empieza pidiendo el correo. */
    public const QUERY_EMAIL = 'bot_query_email';

    /** El texto recibido no parece un correo. */
    public const QUERY_EMAIL_INVALID = 'bot_query_email_invalid';

    /** Con el correo ya recibido, la consulta pide el documento. */
    public const QUERY_DOCUMENT = 'bot_query_document';

    /** El texto recibido no parece un documento. */
    public const QUERY_DOCUMENT_INVALID = 'bot_query_document_invalid';

    /** El correo y el documento no coinciden con el padron. */
    public const QUERY_MISMATCH = 'bot_query_mismatch';

    /** Se agotaron los intentos de una consulta. */
    public const QUERY_TOO_MANY_ATTEMPTS = 'bot_query_too_many_attempts';

    /** El padron de consulta no esta disponible. */
    public const QUERY_DISABLED = 'bot_query_disabled';

    /** Linea de la lista de cursos. */
    public const COURSE_LINE = 'bot_course_line';

    /** Cierre de la lista cuando hay mas cursos de los que caben en el mensaje. */
    public const COURSES_MORE = 'bot_courses_more';

    /** Lista de cursos de pago disponibles. */
    public const COURSES = 'bot_courses';

    /** La persona existe, pero no tiene cursos de pago vigentes. */
    public const COURSES_EMPTY = 'bot_courses_empty';

    /** Nota de suscripcion activa (sin los programas de especializacion). */
    public const COURSES_SUBSCRIPTION = 'bot_courses_subscription';

    /** Nota de suscripcion Premium VIP (incluye especializacion). */
    public const COURSES_SUBSCRIPTION_VIP = 'bot_courses_subscription_vip';

    /** Linea de la lista de certificados. */
    public const CERTIFICATE_LINE = 'bot_certificate_line';

    /** Linea que agrega el modulo a un certificado de modulo. */
    public const CERTIFICATE_MODULE = 'bot_certificate_module';

    /** Lista de certificados. */
    public const CERTIFICATES = 'bot_certificates';

    /** La persona existe, pero no tiene certificados. */
    public const CERTIFICATES_EMPTY = 'bot_certificates_empty';

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
                'variables' => ['{menu}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                👋 ¡Hola! Soy el bot de avisos de la institución.

                Para activar los avisos en este chat, escríbeme tu <b>número de documento (DNI)</b>, solo el número.

                {menu}
                TXT,
            ],
            self::START_REGISTERED => [
                'name' => 'Pedido del documento (chat ya registrado)',
                'description' => 'Se envia en lugar del anterior cuando ese chat ya figura como registrado.',
                'variables' => ['{nombre}', '{menu}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                👋 Hola {nombre}, este chat ya está registrado.

                Si quieres asociarlo a otro documento, escríbelo aquí.

                {menu}
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
            self::MENU => [
                'name' => 'Menú de opciones',
                'description' => 'Lista de lo que el bot puede hacer. Se incluye en el saludo y en el mensaje de un chat ya registrado.',
                'variables' => [],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                Estas son las opciones de este bot:

                /start – Registrar o actualizar tu documento.
                /cursos – Ver tus cursos de pago disponibles.
                /certificados – Ver los certificados que tienes emitidos.
                /chatid – Ver el identificador de este chat.
                /ayuda – Ver esta ayuda.
                /baja – Dejar de recibir avisos.
                TXT,
            ],
            self::HELP => [
                'name' => 'Ayuda (/ayuda)',
                'description' => 'Explica cómo registrarse, consultar datos y darse de baja.',
                'variables' => [],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                ℹ️ Soy el bot de avisos de la institución.

                Para activar los avisos en este chat: escribe /start y luego tu número de documento (DNI).

                Para consultar tus cursos de pago: /cursos.

                Para consultar tus certificados: /certificados.

                Para ver el identificador de este chat: /chatid.

                Para dejar de recibir avisos: /baja.
                TXT,
            ],
            self::CHAT_ID => [
                'name' => 'Identificador del chat (/chatid)',
                'description' => 'Devuelve el chat_id de Telegram de quien consulta, para compartirlo con la institución.',
                'variables' => ['{chat_id}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                🆔 El identificador de este chat es:

                <code>{chat_id}</code>

                Compártelo con la institución si necesitas ayuda con tus avisos.
                TXT,
            ],
            self::QUERY_EMAIL => [
                'name' => 'Consulta: pedido del correo',
                'description' => 'Primer paso de /cursos y /certificados: pide el correo registrado.',
                'variables' => [],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                🔒 Para consultar tu información necesito confirmar tu identidad.

                Escríbeme el <b>correo electrónico</b> que registraste con la institución.
                TXT,
            ],
            self::QUERY_EMAIL_INVALID => [
                'name' => 'Consulta: correo no reconocido',
                'description' => 'El mensaje no parece un correo electrónico.',
                'variables' => ['{intentos}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                🤔 No reconocí eso como un correo electrónico.

                Escríbelo completo, por ejemplo: nombre@correo.com. ({intentos})
                TXT,
            ],
            self::QUERY_DOCUMENT => [
                'name' => 'Consulta: pedido del documento',
                'description' => 'Segundo paso de /cursos y /certificados: ya recibió el correo y pide el documento.',
                'variables' => [],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                Gracias. Ahora escríbeme tu <b>número de documento (DNI)</b>, solo el número.
                TXT,
            ],
            self::QUERY_DOCUMENT_INVALID => [
                'name' => 'Consulta: documento no reconocido',
                'description' => 'El mensaje no parece un número de documento.',
                'variables' => ['{intentos}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                🤔 No reconocí eso como un número de documento.

                Escríbeme solo el número, sin espacios ni guiones. ({intentos})
                TXT,
            ],
            self::QUERY_MISMATCH => [
                'name' => 'Consulta: datos que no coinciden',
                'description' => 'El correo y el documento no pertenecen a la misma persona del padrón. No se distingue cuál falló.',
                'variables' => ['{intentos}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                🔒 El correo y el documento no coinciden con nuestros registros.

                Verifícalos y vuelve a intentarlo. ({intentos})
                TXT,
            ],
            self::QUERY_TOO_MANY_ATTEMPTS => [
                'name' => 'Consulta: demasiados intentos',
                'description' => 'Se agotaron los intentos de verificación y el bot cierra la consulta.',
                'variables' => [],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                🚫 Demasiados intentos seguidos.

                Escribe /cursos o /certificados para volver a intentarlo.
                TXT,
            ],
            self::QUERY_DISABLED => [
                'name' => 'Consulta: no disponible',
                'description' => 'No hay padrón vinculado para validar el correo y el documento (módulo no disponible).',
                'variables' => [],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                ⚠️ La consulta por Telegram no está habilitada en este momento.

                Contacta a la institución.
                TXT,
            ],
            self::COURSE_LINE => [
                'name' => 'Consulta: línea de curso',
                'description' => 'Cada curso de la lista. {tipo} y {vigencia} son opcionales: si vienen vacíos, su línea no se envía.',
                'variables' => ['{curso}', '{tipo}', '{vigencia}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                • <b>{curso}</b>
                {tipo}
                {vigencia}
                TXT,
            ],
            self::COURSES_MORE => [
                'name' => 'Consulta: cursos adicionales',
                'description' => 'Se agrega al final de la lista cuando hay más cursos que el máximo configurado.',
                'variables' => ['{total}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                …y {total} cursos más.
                TXT,
            ],
            self::COURSES => [
                'name' => 'Consulta: lista de cursos',
                'description' => 'Cierre de /cursos. {cursos} son los cursos de pago vigentes y {suscripcion} la nota de suscripción; si vienen vacíos, su línea no se envía.',
                'variables' => ['{nombre}', '{cursos}', '{suscripcion}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                ✅ Hola {nombre}, estos son tus cursos de pago disponibles:

                {cursos}

                {suscripcion}

                Recuerda que puedes escribir /certificados para ver tus certificados.
                TXT,
            ],
            self::COURSES_EMPTY => [
                'name' => 'Consulta: sin cursos',
                'description' => 'La persona existe, pero no tiene cursos de pago vigentes ni suscripción.',
                'variables' => ['{nombre}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                😕 Hola {nombre}, no encontramos cursos de pago vigentes a tu nombre.

                Si crees que es un error, contacta a la institución.
                TXT,
            ],
            self::COURSES_SUBSCRIPTION => [
                'name' => 'Consulta: nota de suscripción',
                'description' => 'Se antepone a la lista cuando la persona tiene suscripción activa. La suscripción no incluye los Programas de Especialización; solo el plan Premium VIP los incluye.',
                'variables' => ['{hasta}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                💳 Tienes una suscripción activa: incluye todos los cursos de pago, excepto los Programas de Especialización.

                📅 Vigencia: {hasta}
                TXT,
            ],
            self::COURSES_SUBSCRIPTION_VIP => [
                'name' => 'Consulta: nota de suscripción Premium VIP',
                'description' => 'Se antepone a la lista cuando la suscripción es Premium VIP, que sí incluye los Programas de Especialización.',
                'variables' => ['{hasta}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                💳 Tu plan Premium VIP está activo e incluye también los Programas de Especialización.

                📅 Vigencia: {hasta}
                TXT,
            ],
            self::CERTIFICATE_LINE => [
                'name' => 'Consulta: línea de certificado',
                'description' => 'Cada certificado de la lista. {modulo} es la línea del módulo (vacía en los certificados de curso). El bot solo informa: la descarga se hace desde la plataforma.',
                'variables' => ['{curso}', '{modulo}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                • <b>{curso}</b>
                {modulo}
                TXT,
            ],
            self::CERTIFICATE_MODULE => [
                'name' => 'Consulta: línea de módulo',
                'description' => 'Se usa dentro de la línea del certificado cuando corresponde a un módulo.',
                'variables' => ['{modulo}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                  📘 Módulo: {modulo}
                TXT,
            ],
            self::CERTIFICATES => [
                'name' => 'Consulta: lista de certificados',
                'description' => 'Cierre de /certificados. {certificados} son las líneas del catálogo de certificados y {enlace} el enlace a la plataforma donde se descargan: el bot los lista, pero no entrega el archivo.',
                'variables' => ['{nombre}', '{certificados}', '{enlace}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                🎓 Hola {nombre}, estos son los certificados que tienes emitidos:

                {certificados}

                🔒 Para descargarlos, <a href="{enlace}">ingresa a la plataforma</a>.
                TXT,
            ],
            self::CERTIFICATES_EMPTY => [
                'name' => 'Consulta: sin certificados',
                'description' => 'La persona existe, pero todavía no tiene certificados emitidos.',
                'variables' => ['{nombre}'],
                'format' => self::FORMAT_HTML,
                'body' => <<<'TXT'
                😕 Hola {nombre}, todavía no tienes certificados emitidos.

                Cuando completes un curso con certificado, aparecerá aquí.
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
