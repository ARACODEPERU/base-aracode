<?php

namespace Modules\Commercial\Support;

use App\Models\Parameter;

/**
 * Enlaces legales que el formulario publico de una negociacion muestra como checks
 * obligatorios: terminos y condiciones, politica de devoluciones y reembolsos, y
 * politica de privacidad.
 *
 * Cada enlace se guarda en un parametro (PN00002, PN00003 y PN00004) para poder
 * cambiarlo desde Comercial > Configuracion sin tocar codigo. Si un parametro queda
 * vacio se usa el valor por defecto, asi el formulario nunca se queda sin enlace.
 */
class CommercialLegalLinks
{
    /** Parametros donde vive cada enlace. */
    public const TERMINOS = 'PN00002';

    public const DEVOLUCIONES = 'PN00003';

    public const PRIVACIDAD = 'PN00004';

    /**
     * Clave que usan el formulario publico y la pagina de configuracion.
     */
    public const KEYS = [
        'terminos' => self::TERMINOS,
        'devoluciones' => self::DEVOLUCIONES,
        'privacidad' => self::PRIVACIDAD,
    ];

    /**
     * Valor por defecto cuando el parametro esta vacio. Los del propio sitio se
     * guardan como ruta y se completan con el dominio al momento de usarlos.
     */
    public const DEFAULTS = [
        'terminos' => '/terminos-condiciones',
        'devoluciones' => 'https://academy.globalcpaperu.com/politicas-de-devolucion',
        'privacidad' => '/politica-privacidad',
    ];

    /**
     * Enlaces listos para enlazar en el formulario: la ruta del propio sitio se
     * convierte en URL absoluta y una URL externa viaja tal cual.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $links = [];

        foreach (self::KEYS as $key => $code) {
            $links[$key] = self::resolve($code, self::DEFAULTS[$key]);
        }

        return $links;
    }

    /**
     * Valor guardado sin resolver, que es lo que se edita en Comercial > Configuracion.
     *
     * @return array<string, string>
     */
    public static function stored(): array
    {
        $values = [];

        foreach (self::KEYS as $key => $code) {
            $values[$key] = self::storedValue($code) ?? self::DEFAULTS[$key];
        }

        return $values;
    }

    /**
     * Guarda los enlaces en sus parametros. Un valor vacio vuelve a dejar el enlace en
     * su valor por defecto.
     *
     * @param  array<string, string|null>  $values
     */
    public static function save(array $values): void
    {
        foreach (self::KEYS as $key => $code) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            Parameter::where('parameter_code', $code)->update([
                'value_default' => trim((string) $values[$key]) ?: self::DEFAULTS[$key],
            ]);
        }
    }

    /**
     * Etiqueta visible de cada enlace, la misma que se muestra en los checks.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'terminos' => 'He leido los terminos y condiciones',
            'devoluciones' => 'He leido Politica de devoluciones y Reembolsos - CPA Academy',
            'privacidad' => 'He leido la Politica de Privacidad',
        ];
    }

    private static function resolve(string $code, string $default): string
    {
        $value = self::storedValue($code) ?? $default;

        return preg_match('#^https?://#i', $value) ? $value : url($value);
    }

    private static function storedValue(string $code): ?string
    {
        $value = Parameter::where('parameter_code', $code)->value('value_default');

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
