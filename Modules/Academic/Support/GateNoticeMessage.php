<?php

namespace Modules\Academic\Support;

/**
 * Texto del aviso de asistencia que viaja en el SMS.
 *
 * El administrador escribe la plantilla con variables entre llaves (por
 * ejemplo {alumno} o {hora}); aquí se reemplazan por los datos reales de la
 * asistencia. Las variables desconocidas se dejan tal cual para que el error se
 * vea al revisar la vista previa.
 */
class GateNoticeMessage
{
    /**
     * Variables disponibles (para la ayuda de la pantalla).
     *
     * @return array<int, array{code: string, label: string, example: string}>
     */
    public static function variables(): array
    {
        return [
            ['code' => '{alumno}', 'label' => 'Nombre del alumno', 'example' => 'Ana Pérez'],
            ['code' => '{apoderado}', 'label' => 'Nombre del padre o apoderado', 'example' => 'Luis Pérez'],
            ['code' => '{colegio}', 'label' => 'Nombre del colegio', 'example' => 'I.E. San Martín'],
            ['code' => '{seccion}', 'label' => 'Grado y sección', 'example' => 'Primaria 3° A'],
            ['code' => '{fecha}', 'label' => 'Fecha del registro', 'example' => '14/03/2026'],
            ['code' => '{hora}', 'label' => 'Hora del registro', 'example' => '07:45'],
            ['code' => '{estado}', 'label' => 'Estado (Asistencia, Tardanza, Salida anticipada...)', 'example' => 'Tardanza'],
        ];
    }

    /**
     * Valores de ejemplo para la vista previa y la prueba.
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
     * Reemplaza las variables conocidas.
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
}
