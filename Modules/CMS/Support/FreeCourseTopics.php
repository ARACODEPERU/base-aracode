<?php

namespace Modules\CMS\Support;

/**
 * Temas de interés que el visitante marca en el paso 2 de la landing de cursos
 * gratis.
 *
 * La clave es lo que se guarda en la base de datos (cms_subscribers.interests) y
 * la etiqueta es lo que se muestra en la landing, en el panel de registros y en
 * las exportaciones. Al centralizarlo aquí, la lista del formulario, la
 * validación del servidor y las etiquetas que se muestran luego no pueden
 * quedar desalineadas.
 */
class FreeCourseTopics
{
    /**
     * Clave => etiqueta visible. El orden es el que se muestra en la landing.
     */
    public const TEMAS = [
        'derecho_tributario' => 'Derecho tributario',
        'derecho_laboral' => 'Derecho laboral',
        'niif_contabilidad' => 'NIIF de Contabilidad',
        'normas_auditoria' => 'Normas internacionales de Auditoría',
        'coaching_liderazgo' => 'Coaching & Liderazgo',
        'ia_contabilidad' => 'Inteligencia artificial aplicada a la contabilidad',
        'niif_s1_s2' => 'NIIF S1 y S2',
        'finanzas_corporativas' => 'Finanzas Corporativas',
        'ciberseguridad' => 'Ciberseguridad',
    ];

    /**
     * Claves válidas, en el orden del catálogo.
     */
    public static function claves(): array
    {
        return array_keys(self::TEMAS);
    }

    /**
     * ¿La clave existe en el catálogo?
     */
    public static function existe(string $clave): bool
    {
        return array_key_exists($clave, self::TEMAS);
    }

    /**
     * Etiqueta de una clave; null si no existe.
     */
    public static function etiqueta(?string $clave): ?string
    {
        return $clave !== null && self::existe($clave) ? self::TEMAS[$clave] : null;
    }

    /**
     * Etiquetas legibles de una lista de claves, sin repetir y en el orden del
     * catálogo (no en el orden en que llegaron).
     */
    public static function etiquetas(?array $claves): array
    {
        $claves = array_map('strval', $claves ?? []);

        return array_values(array_filter(array_map(
            fn (string $clave, string $etiqueta) => in_array($clave, $claves, true) ? $etiqueta : null,
            array_keys(self::TEMAS),
            self::TEMAS
        )));
    }

    /**
     * Lista lista para recorrer en la vista: [['clave' => .., 'etiqueta' => ..]].
     */
    public static function paraVista(): array
    {
        $opciones = [];

        foreach (self::TEMAS as $clave => $etiqueta) {
            $opciones[] = ['clave' => $clave, 'etiqueta' => $etiqueta];
        }

        return $opciones;
    }
}
