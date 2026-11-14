<?php

namespace App\Support;

use App\Models\Parameter;

/**
 * Reparte el HTML/scripts guardados en el parametro P000022 entre el head y el
 * body de las paginas. Acepta codigo arbitrario y el snippet completo de GTM.
 *
 * Reglas:
 *  - Los marcadores <!-- HEAD --> y <!-- BODY --> separan explicitamente ambas partes.
 *  - Un snippet completo de GTM se corta en su comentario "Google Tag Manager (noscript)".
 *  - Si solo viene el script de GTM, el noscript se genera con el ID del contenedor.
 *  - Los bloques <noscript> se envian al body.
 *  - El HTML de cuerpo (div, iframe, img, etc.) se envia al body.
 *  - Scripts, estilos, metas y links se quedan en el head.
 */
class PageSnippet
{
    public const PARAMETER_CODE = 'P000022';

    private const GTM_NOSCRIPT_MARKER = '<!-- Google Tag Manager (noscript) -->';

    private const BODY_MARKER = '/<!--\s*(?:P000022:)?BODY\s*-->/i';

    private const HEAD_MARKER = '/<!--\s*(?:P000022:)?HEAD\s*-->/i';

    private const BODY_ONLY_TAGS = '/<(?:div|section|article|header|footer|main|aside|nav|p|span|a|img|picture|iframe|video|audio|canvas|svg|table|form|input|select|textarea|button|ul|ol|li|h[1-6])\b/i';

    /**
     * @return array{head: string, body: string}
     */
    public static function parts(): array
    {
        $html = self::raw();

        if ($html === '') {
            return ['head' => '', 'body' => ''];
        }

        // 1. Separacion explicita con marcadores.
        if (preg_match(self::BODY_MARKER, $html)) {
            [$head, $body] = preg_split(self::BODY_MARKER, $html, 2);
            $head = preg_replace(self::HEAD_MARKER, '', $head);

            return ['head' => trim($head), 'body' => trim($body)];
        }

        if (preg_match(self::HEAD_MARKER, $html)) {
            return [
                'head' => trim(preg_replace(self::HEAD_MARKER, '', $html)),
                'body' => '',
            ];
        }

        // 2. Snippet completo de Google Tag Manager (script + noscript).
        if (str_contains($html, self::GTM_NOSCRIPT_MARKER)) {
            [$head, $body] = explode(self::GTM_NOSCRIPT_MARKER, $html, 2);

            return [
                'head' => trim($head),
                'body' => self::GTM_NOSCRIPT_MARKER . "\n" . trim($body),
            ];
        }

        // 3. Solo el script de GTM: el noscript se genera con el ID, salvo que
        //    ya venga en el codigo. Los <noscript> que haya (p. ej. un pixel) van al body.
        if (preg_match('/GTM-[A-Z0-9]+/', $html, $matches)) {
            $containerId = $matches[0];
            $noscripts = [];

            $head = preg_replace_callback(
                '/<noscript\b[^>]*>.*?<\/noscript>/is',
                function (array $found) use (&$noscripts) {
                    $noscripts[] = $found[0];

                    return '';
                },
                $html
            );

            $body = [];

            if (! str_contains($html, 'googletagmanager.com/ns.html')) {
                $body[] = self::GTM_NOSCRIPT_MARKER . "\n"
                    . '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . $containerId . '"'
                    . ' height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n"
                    . '<!-- End Google Tag Manager (noscript) -->';
            }

            array_push($body, ...$noscripts);

            return [
                'head' => trim($head),
                'body' => trim(implode("\n", $body)),
            ];
        }

        // 4. Cualquier <noscript> pertenece al body.
        if (preg_match_all('/<noscript\b[^>]*>.*?<\/noscript>/is', $html, $matches)) {
            return [
                'head' => trim(str_replace($matches[0], '', $html)),
                'body' => trim(implode("\n", $matches[0])),
            ];
        }

        // 5. Etiquetas de contenido (widgets, imagenes, iframes...) van al body.
        if (preg_match(self::BODY_ONLY_TAGS, $html)) {
            return ['head' => '', 'body' => trim($html)];
        }

        // 6. Scripts, estilos, metas y links se quedan en el head.
        return ['head' => trim($html), 'body' => ''];
    }

    /**
     * Indica si el contenido guardado en P000022 incluye el texto dado.
     */
    public static function contains(string $needle): bool
    {
        return $needle !== '' && str_contains(self::raw(), $needle);
    }

    private static function raw(): string
    {
        $value = trim((string) Parameter::query()
            ->where('parameter_code', self::PARAMETER_CODE)
            ->value('value_default'));

        // El parametro pudo guardarse escapado una o varias veces desde el
        // formulario de parametros; se decodifica hasta obtener el HTML real.
        for ($i = 0; $i < 3; $i++) {
            $decoded = htmlspecialchars_decode($value, ENT_QUOTES);

            if ($decoded === $value) {
                break;
            }

            $value = $decoded;
        }

        return trim($value);
    }
}
