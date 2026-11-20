<?php

namespace Modules\Sales\Services\Sire;

/**
 * Comparación entre la propuesta que devuelve SUNAT y el registro que genera
 * el ERP, antes de reemplazarla.
 *
 * Ambos archivos son TXT con el mismo anexo, así que se comparan línea contra
 * línea usando la clave tipo + serie + número. Se detectan comprobantes que
 * SUNAT tiene y el ERP no (`faltan_en_erp`), comprobantes del ERP que SUNAT no
 * tiene (`faltan_en_sunat`) y diferencias de campos en los que coinciden.
 *
 * Los importes se comparan con tolerancia de dos céntimos para no marcar
 * diferencias por redondeo.
 */
class SireComparadorService
{
    /** Índice de cada campo dentro de la línea, por registro. */
    private const CAMPOS = [
        'ventas' => [
            'fecha' => 4,
            'tipo' => 6,
            'serie' => 7,
            'numero' => 8,
            'doc_tipo' => 10,
            'doc_numero' => 11,
            'nombre' => 12,
            'gravada' => 14,
            'igv' => 16,
            'total' => 25,
            'moneda' => 26,
            'tipo_cambio' => 27,
        ],
        'compras' => [
            'fecha' => 4,
            'tipo' => 6,
            'serie' => 7,
            'numero' => 9,
            'doc_tipo' => 11,
            'doc_numero' => 12,
            'nombre' => 13,
            'gravada' => 14,
            'igv' => 15,
            'total' => 24,
            'moneda' => 25,
            'tipo_cambio' => 26,
        ],
    ];

    /** Campos numéricos, comparados con tolerancia. */
    private const NUMERICOS = ['gravada', 'igv', 'total', 'tipo_cambio'];

    /** Cantidad de campos del anexo en cada registro. */
    private const TOTAL_CAMPOS = [
        'ventas' => 33,
        'compras' => 37,
    ];

    private const TOLERANCIA = 0.02;

    /** Tope de elementos que se devuelven a la pantalla por lista. */
    private const MAX_ITEMS = 100;

    /**
     * @return array<string, mixed>
     */
    public function comparar(string $tipo, string $nuestro, string $sunat): array
    {
        $tipo = $tipo === 'compras' ? 'compras' : 'ventas';
        $campos = self::CAMPOS[$tipo];
        $esperados = self::TOTAL_CAMPOS[$tipo];

        $erp = $this->indexar($nuestro, $campos, $esperados);
        $propuesta = $this->indexar($sunat, $campos, $esperados);

        $faltanEnErp = [];
        $faltanEnSunat = [];
        $diferencias = [];

        foreach ($propuesta['lineas'] as $clave => $linea) {
            if (! isset($erp['lineas'][$clave])) {
                $faltanEnErp[] = $this->resumenLinea($linea, $campos);

                continue;
            }

            foreach ($campos as $campo => $indice) {
                if (in_array($campo, ['tipo', 'serie', 'numero'], true)) {
                    continue;
                }

                $valorSunat = trim((string) ($linea[$indice] ?? ''));
                $valorErp = trim((string) ($erp['lineas'][$clave][$indice] ?? ''));

                if ($this->iguales($campo, $valorSunat, $valorErp)) {
                    continue;
                }

                $diferencias[] = [
                    'clave' => $clave,
                    'numero' => $this->numero($linea, $campos),
                    'campo' => $campo,
                    'campo_etiqueta' => $this->etiqueta($campo),
                    'sunat' => $valorSunat,
                    'erp' => $valorErp,
                ];
            }
        }

        foreach ($erp['lineas'] as $clave => $linea) {
            if (! isset($propuesta['lineas'][$clave])) {
                $faltanEnSunat[] = $this->resumenLinea($linea, $campos);
            }
        }

        $iguales = 0;

        foreach ($erp['lineas'] as $clave => $linea) {
            if (isset($propuesta['lineas'][$clave])
                && ! $this->tieneDiferencias($diferencias, (string) $clave)) {
                $iguales++;
            }
        }

        $resumen = [
            'erp_lineas' => count($erp['lineas']),
            'sunat_lineas' => count($propuesta['lineas']),
            'iguales' => $iguales,
            'diferencias' => count($diferencias),
            'faltan_en_erp' => count($faltanEnErp),
            'faltan_en_sunat' => count($faltanEnSunat),
        ];

        // Una línea ilegible de cualquiera de los dos lados impide asegurar que
        // la comparación esté completa.
        $incompleto = $erp['invalidas'] > 0 || $propuesta['invalidas'] > 0;

        return [
            'ok' => $diferencias === [] && $faltanEnErp === [] && $faltanEnSunat === [] && ! $incompleto,
            'resumen' => $resumen,
            'faltan_en_erp' => array_slice($faltanEnErp, 0, self::MAX_ITEMS),
            'faltan_en_sunat' => array_slice($faltanEnSunat, 0, self::MAX_ITEMS),
            'diferencias' => array_slice($diferencias, 0, self::MAX_ITEMS),
            'truncado' => count($faltanEnErp) > self::MAX_ITEMS
                || count($faltanEnSunat) > self::MAX_ITEMS
                || count($diferencias) > self::MAX_ITEMS,
            'lineas_invalidas_erp' => $erp['invalidas'],
            'lineas_invalidas_sunat' => $propuesta['invalidas'],
            'message' => $this->mensaje($resumen, $incompleto),
        ];
    }

    /**
     * Texto del resultado, listo para la pantalla.
     *
     * @param  array<string, int>  $resumen
     */
    private function mensaje(array $resumen, bool $incompleto): string
    {
        if ($resumen['erp_lineas'] === 0 && $resumen['sunat_lineas'] === 0) {
            return 'El período no tiene operaciones en el ERP ni en la propuesta de SUNAT.';
        }

        if ($resumen['diferencias'] === 0
            && $resumen['faltan_en_erp'] === 0
            && $resumen['faltan_en_sunat'] === 0
            && ! $incompleto) {
            return 'El registro del ERP coincide con la propuesta de SUNAT en los '
                . $resumen['iguales'] . ' comprobante(s) comparados.';
        }

        return 'La comparación encontró ' . $resumen['diferencias'] . ' diferencia(s) de datos, '
            . $resumen['faltan_en_erp'] . ' comprobante(s) que SUNAT tiene y el ERP no, y '
            . $resumen['faltan_en_sunat'] . ' comprobante(s) del ERP que SUNAT no tiene'
            . ($incompleto ? '. Además hay líneas ilegibles en alguno de los dos archivos.' : '.');
    }

    /**
     * @param  array<string, int>  $campos
     * @return array{lineas: array<string, array<int, string>>, invalidas: int}
     */
    private function indexar(string $contenido, array $campos, int $esperados): array
    {
        $lineas = [];
        $invalidas = 0;

        foreach (preg_split('/\r\n|\r|\n/', $contenido) ?: [] as $linea) {
            if (trim($linea) === '') {
                continue;
            }

            $datos = explode('|', $linea);

            if (count($datos) !== $esperados) {
                $invalidas++;

                continue;
            }

            $clave = $this->clave($datos, $campos);

            if ($clave === '') {
                $invalidas++;

                continue;
            }

            // Si el archivo repite un comprobante, se conserva el primero: la
            // comparación avisa de la línea repetida como parte del conteo.
            $lineas[$clave] ??= $datos;
        }

        return ['lineas' => $lineas, 'invalidas' => $invalidas];
    }

    /**
     * @param  array<int, string>  $datos
     * @param  array<string, int>  $campos
     */
    private function clave(array $datos, array $campos): string
    {
        $tipo = trim((string) ($datos[$campos['tipo']] ?? ''));
        $serie = strtoupper(trim((string) ($datos[$campos['serie']] ?? '')));
        $numero = strtoupper(trim((string) ($datos[$campos['numero']] ?? '')));

        if ($serie === '' || $numero === '') {
            return '';
        }

        return $tipo . '|' . $serie . '|' . $numero;
    }

    /**
     * @param  array<int, string>  $datos
     * @param  array<string, int>  $campos
     */
    private function numero(array $datos, array $campos): string
    {
        return trim((string) ($datos[$campos['serie']] ?? '') . '-'
            . (string) ($datos[$campos['numero']] ?? ''), '-');
    }

    /**
     * @param  array<int, string>  $datos
     * @param  array<string, int>  $campos
     * @return array<string, mixed>
     */
    private function resumenLinea(array $datos, array $campos): array
    {
        return [
            'clave' => $this->clave($datos, $campos),
            'numero' => $this->numero($datos, $campos),
            'fecha' => trim((string) ($datos[$campos['fecha']] ?? '')),
            'nombre' => trim((string) ($datos[$campos['nombre']] ?? '')),
            'total' => trim((string) ($datos[$campos['total']] ?? '')),
            'moneda' => trim((string) ($datos[$campos['moneda']] ?? '')),
        ];
    }

    private function iguales(string $campo, string $sunat, string $erp): bool
    {
        if (in_array($campo, self::NUMERICOS, true)) {
            // Un campo vacío y un cero en tipo de cambio significan lo mismo.
            if ($sunat === '' && $erp === '') {
                return true;
            }

            if (! is_numeric($sunat) || ! is_numeric($erp)) {
                return $sunat === $erp;
            }

            return abs((float) $sunat - (float) $erp) <= self::TOLERANCIA;
        }

        return $this->normalizar($campo, $sunat) === $this->normalizar($campo, $erp);
    }

    private function normalizar(string $campo, string $valor): string
    {
        $valor = trim(preg_replace('/\s+/u', ' ', $valor) ?? $valor);

        return in_array($campo, ['nombre', 'moneda', 'doc_numero'], true)
            ? mb_strtoupper($valor)
            : $valor;
    }

    /**
     * @param  array<int, array<string, mixed>>  $diferencias
     */
    private function tieneDiferencias(array $diferencias, string $clave): bool
    {
        foreach ($diferencias as $diferencia) {
            if ($diferencia['clave'] === $clave) {
                return true;
            }
        }

        return false;
    }

    private function etiqueta(string $campo): string
    {
        return [
            'fecha' => 'fecha de emisión',
            'doc_tipo' => 'tipo de documento',
            'doc_numero' => 'número de documento',
            'nombre' => 'nombre o razón social',
            'gravada' => 'base gravada',
            'igv' => 'IGV',
            'total' => 'total',
            'moneda' => 'moneda',
            'tipo_cambio' => 'tipo de cambio',
        ][$campo] ?? $campo;
    }
}
