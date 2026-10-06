<?php

namespace Modules\Health\Support;

/**
 * Mensaje de los avisos de citas.
 *
 * El administrador escribe el texto con variables entre llaves; aqui se
 * reemplazan por los datos reales de la cita. El texto admite HTML y emoticonos
 * (por si algun dia se agrega un canal con formato), pero el SMS viaja como
 * texto plano: toPlainText quita las etiquetas y conserva los emoticonos.
 */
class AppointmentNoticeMessage
{
    /** Primer aviso antes de la cita. */
    public const KEY_FIRST = 'first';

    /** Segundo aviso antes de la cita. */
    public const KEY_SECOND = 'second';

    /** Aviso un dia antes de la cita. */
    public const KEY_DAY_BEFORE = 'day_before';

    /**
     * Claves validas de los bloques de aviso.
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return [self::KEY_FIRST, self::KEY_SECOND, self::KEY_DAY_BEFORE];
    }

    /**
     * Variables disponibles en el mensaje (para la ayuda de la pantalla).
     *
     * @return array<int, array{code: string, label: string, example: string}>
     */
    public static function variables(): array
    {
        return [
            ['code' => '{paciente}', 'label' => 'Nombre del paciente', 'example' => 'Ana Pérez'],
            ['code' => '{hora_cita}', 'label' => 'Hora de la cita', 'example' => '10:30'],
            ['code' => '{fecha_cita}', 'label' => 'Fecha de la cita', 'example' => '14/03/2026'],
            ['code' => '{nombre_dr}', 'label' => 'Nombre del doctor', 'example' => 'Dr. Luis Gómez'],
            ['code' => '{clinica}', 'label' => 'Nombre del consultorio', 'example' => 'Mi Consultorio'],
        ];
    }

    /**
     * Valores de ejemplo con los que se arma la vista previa y la prueba.
     *
     * @return array<string, string>
     */
    public static function sampleValues(): array
    {
        $values = [];

        foreach (self::variables() as $variable) {
            $values[trim($variable['code'], '{}')] = $variable['example'];
        }

        return $values;
    }

    /**
     * Reemplaza las variables conocidas. Las variables desconocidas se dejan
     * tal cual para que el error sea visible al revisar la vista previa.
     *
     * @param array<string, string> $values clave sin llaves => valor
     */
    public static function render(?string $template, array $values): string
    {
        $template = (string) $template;

        foreach ($values as $key => $value) {
            $template = str_replace('{' . $key . '}', (string) $value, $template);
        }

        return trim($template);
    }

    /**
     * Version de texto plano para el SMS: conserva los emoticonos y descarta
     * las etiquetas HTML y las lineas vacias que dejan.
     */
    public static function toPlainText(?string $html): string
    {
        $text = (string) $html;

        // Los saltos de linea del HTML se vuelven saltos reales antes de quitar
        // las etiquetas, para no pegar dos frases.
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\/(p|div|li|h[1-6]|tr)>/i', "\n", $text) ?? $text;

        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $lines = array_map('trim', explode("\n", $text));
        $lines = array_values(array_filter($lines, fn (string $line) => $line !== ''));

        return implode("\n", $lines);
    }
}
