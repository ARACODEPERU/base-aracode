<?php

namespace Modules\Academic\Support;

use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Vigencia de los tokens del servidor MCP.
 *
 * El catálogo vive aquí para que el comando `mcp:token` y la pantalla de
 * tokens ofrezcan exactamente las mismas opciones y las mismas fechas. Un
 * token «indefinido» se guarda con `expires_at` nulo, que es como Sanctum
 * expresa que no vence.
 */
class McpTokenLifetime
{
    /**
     * Clave interna => texto que se muestra.
     *
     * @var array<string, string>
     */
    public const OPCIONES = [
        '1_semana' => '1 semana',
        '2_semanas' => '2 semanas',
        '1_mes' => '1 mes',
        '2_meses' => '2 meses',
        '3_meses' => '3 meses',
        '6_meses' => '6 meses',
        '1_anio' => '1 año',
        '2_anios' => '2 años',
        'indefinido' => 'Indefinido (no vence)',
    ];

    /**
     * Valor marcado por defecto: 90 días, la misma vida que tenían los tokens
     * emitidos antes de existir el selector.
     */
    public const POR_DEFECTO = '3_meses';

    /**
     * Claves válidas, en el orden en que se ofrecen.
     *
     * @return array<int, string>
     */
    public static function claves(): array
    {
        return array_keys(self::OPCIONES);
    }

    public static function existe(string $clave): bool
    {
        return array_key_exists($clave, self::OPCIONES);
    }

    /**
     * Días de vida del token; null cuando no vence.
     */
    public static function dias(string $clave): ?int
    {
        $dias = match ($clave) {
            '1_semana' => 7,
            '2_semanas' => 14,
            '1_mes' => 30,
            '2_meses' => 60,
            '3_meses' => 90,
            '6_meses' => 180,
            '1_anio' => 365,
            '2_anios' => 730,
            'indefinido' => null,
            default => throw new InvalidArgumentException('Vigencia de token no válida: ' . $clave),
        };

        return $dias;
    }

    /**
     * Fecha que se guarda en `personal_access_tokens.expires_at`.
     */
    public static function vencimiento(string $clave): ?Carbon
    {
        $dias = self::dias($clave);

        return $dias === null ? null : now()->addDays($dias);
    }

    public static function etiqueta(string $clave): string
    {
        return self::OPCIONES[$clave] ?? $clave;
    }

    /**
     * Catálogo listo para la pantalla: valor y texto de cada opción.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function paraVista(): array
    {
        $opciones = [];

        foreach (self::OPCIONES as $valor => $etiqueta) {
            $opciones[] = ['value' => $valor, 'label' => $etiqueta];
        }

        return $opciones;
    }
}
