<?php

namespace Modules\Sales\Services\Sire;

use Carbon\Carbon;

/**
 * Formato común de los archivos planos del SIRE (RVIE y RCE): importes con dos
 * decimales, fechas DD/MM/AAAA, texto libre sin pipes, tipo de cambio de tres
 * decimales, líneas separadas por "|" y nombre de archivo de la tabla 6 / 13.
 */
class SireArchivoPlano
{
    /** Registro de ventas e ingresos (Anexo N.° 3). */
    public const LIBRO_VENTAS = '140400';

    /** Registro de compras (Anexo N.° 11). */
    public const LIBRO_COMPRAS = '080400';

    public static function money(float $value): string
    {
        return number_format(round($value, 2), 2, '.', '');
    }

    public static function date($value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * El texto libre no debe contener pipes ni barras invertidas.
     */
    public static function text(string $value): string
    {
        return str_replace(['|', '\\'], ' ', trim($value));
    }

    /**
     * En soles el campo queda vacío; en moneda extranjera va con tres decimales.
     */
    public static function exchangeRate(string $currency, ?float $rate): string
    {
        if ($currency === 'PEN' || ! $rate) {
            return '';
        }

        return number_format($rate, 3, '.', '');
    }

    /**
     * @param  array<int, string>  $lines
     */
    public static function content(array $lines): string
    {
        return $lines === [] ? '' : implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Nombre según la tabla 6 (RVIE) y 13 (RCE): LE + RUC + AAAAMM + DD +
     * identificador del libro + oportunidad 02 + indicadores O I M G.
     */
    public static function fileName(string $libro, string $ruc, string $period, bool $hasOperations): string
    {
        return 'LE' . $ruc . str_replace('-', '', $period)
            . '00' . $libro . '02'
            . '1' . ($hasOperations ? '1' : '0') . '1' . '2'
            . '.TXT';
    }

    /**
     * Comprime el TXT en un ZIP temporal, como exige la carga en SOL.
     */
    public static function zip(string $content, string $fileName): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sire_') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString($fileName, $content);
        $zip->close();

        return $path;
    }
}
