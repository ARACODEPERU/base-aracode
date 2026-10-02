<?php

namespace Modules\Integrationhub\Support;

/**
 * Prepara un texto para la API de Telegram (parse_mode HTML).
 *
 * Telegram interpreta las etiquetas que conoce y, si el texto trae una "&" o un
 * "<" sueltos, rechaza la peticion entera con HTTP 400 ("can't parse entities")
 * y el aviso se pierde. Por eso cada texto se prepara antes de salir:
 *
 *   - modo HTML: se respetan las etiquetas permitidas (con el href escapado por
 *     dentro) y se escapan los simbolos sueltos del resto;
 *   - modo texto: se escapa todo, de modo que quien escriba <b> lo vea tal cual
 *     en el chat.
 *
 * Los emojis y los saltos de linea pasan intactos: son texto normal de Telegram.
 */
class TelegramHtml
{
    /** Etiquetas que acepta la API de Telegram en parse_mode HTML. */
    private const TAGS = 'b|strong|i|em|u|ins|s|strike|del|code|pre|tg-spoiler';

    /** Marcador temporal para apartar las etiquetas mientras se escapa el resto. */
    private const PLACEHOLDER = "\x1A";

    public static function prepare(string $text, bool $html): string
    {
        return $html ? self::forHtmlMode($text) : self::forTextMode($text);
    }

    /**
     * Texto literal: ninguna etiqueta se interpreta.
     */
    private static function forTextMode(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', true);
    }

    /**
     * Texto con formato: se conservan solo las etiquetas que Telegram entiende.
     */
    private static function forHtmlMode(string $text): string
    {
        $tags = [];

        // 1. Se apartan las etiquetas permitidas (y se escapa su href).
        $rest = (string) preg_replace_callback(
            '#<a\s+href="[^"]*"\s*>|</a>|</?(?:' . self::TAGS . ')>#i',
            function (array $match) use (&$tags) {
                $tags[] = self::escapeHref($match[0]);

                return self::PLACEHOLDER . (count($tags) - 1) . self::PLACEHOLDER;
            },
            $text
        );

        // 2. Se escapan los simbolos sueltos sin duplicar las entidades que ya
        //    vienen escritas (&amp;, &lt;, ...).
        $escaped = htmlspecialchars($rest, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);

        // 3. Se devuelven las etiquetas a su sitio.
        return (string) preg_replace_callback(
            '/' . self::PLACEHOLDER . '(\d+)' . self::PLACEHOLDER . '/',
            fn (array $match) => $tags[(int) $match[1]] ?? '',
            $escaped
        );
    }

    /**
     * La URL de un enlace tambien viaja dentro de HTML: una "&" en los
     * parametros romperia el parseo.
     */
    private static function escapeHref(string $tag): string
    {
        return (string) preg_replace_callback(
            '#href="([^"]*)"#i',
            fn (array $match) => 'href="' . htmlspecialchars($match[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false) . '"',
            $tag
        );
    }
}
