<?php

namespace Modules\Sales\Services\Sire;

/**
 * Validador interno de los archivos planos del SIRE.
 *
 * Bloquea la descarga del TXT/ZIP cuando el archivo no podría ser aceptado:
 * RUC de la empresa inválido, campos obligatorios vacíos, documento de
 * identidad con longitud incorrecta, notas sin referencia, moneda extranjera
 * sin tipo de cambio o importes que no cuadran.
 *
 * Devuelve mensajes listos para mostrar; el servicio los expone como `errors`
 * y la pantalla deshabilita las descargas. Las observaciones del servicio
 * siguen siendo advertencias que no bloquean.
 */
class SireValidador
{
    private const CAMPOS = [
        'ventas' => 33,
        'compras' => 37,
    ];

    /** Tolerancia por redondeo a dos decimales entre la suma y el total. */
    private const TOLERANCIA = 0.02;

    private const MAX_ERRORES = 40;

    /**
     * @return array<int, string>
     */
    public function validar(string $tipo, string $content, string $ruc): array
    {
        $errores = [];
        $campos = self::CAMPOS[$tipo] ?? 33;

        if (strlen((string) preg_replace('/\D/', '', $ruc)) !== 11) {
            $errores[] = 'La empresa no tiene un RUC de 11 dígitos configurado.';
        }

        $lineas = preg_split('/\r\n|\r|\n/', $content) ?: [];

        foreach ($lineas as $linea) {
            if (trim($linea) === '') {
                continue;
            }

            $datos = explode('|', $linea);
            $serie = trim((string) ($datos[7] ?? ''));
            $numero = trim((string) ($tipo === 'compras' ? ($datos[9] ?? '') : ($datos[8] ?? '')));
            $etiqueta = trim($serie . '-' . $numero, '-');

            if (count($datos) !== $campos) {
                $errores[] = $etiqueta . ': la línea tiene ' . count($datos) . ' campos y deben ser ' . $campos . '.';

                continue;
            }

            $delDocumento = $tipo === 'compras'
                ? $this->compras($datos)
                : $this->ventas($datos);

            foreach ($delDocumento as $error) {
                $errores[] = ($etiqueta !== '' ? $etiqueta . ': ' : '') . $error;
            }
        }

        if (count($errores) > self::MAX_ERRORES) {
            $restantes = count($errores) - self::MAX_ERRORES;
            $errores = array_slice($errores, 0, self::MAX_ERRORES);
            $errores[] = 'y ' . $restantes . ' error(es) más en el archivo.';
        }

        return $errores;
    }

    /**
     * @param  array<int, string>  $datos
     * @return array<int, string>
     */
    private function ventas(array $datos): array
    {
        $errores = [];

        $tipo = trim($datos[6]);
        $total = (float) $datos[25];

        if (! $this->fechaValida($datos[4])) {
            $errores[] = 'la fecha de emisión es obligatoria y debe tener formato DD/MM/AAAA.';
        }

        if (! in_array($tipo, ['01', '03', '07', '08'], true)) {
            $errores[] = 'el tipo de comprobante "' . $tipo . '" no forma parte del registro de ventas.';
        }

        if (trim($datos[7]) === '') {
            $errores[] = 'falta la serie del comprobante.';
        }

        if (trim($datos[8]) === '') {
            $errores[] = 'falta el número del comprobante.';
        }

        if (trim($datos[12]) === '') {
            $errores[] = 'falta el nombre o razón social del cliente.';
        }

        $errores = array_merge($errores, $this->documentoCliente($datos, $tipo, $total));

        if (in_array($tipo, ['07', '08'], true)) {
            if (! $this->fechaValida($datos[28])
                || trim($datos[29]) === ''
                || trim($datos[30]) === ''
                || trim($datos[31]) === '') {
                $errores[] = 'la nota no tiene el comprobante de referencia completo (fecha, tipo, serie y número).';
            }
        }

        $moneda = strtoupper(trim($datos[26]));

        if (preg_match('/^[A-Z]{3}$/', $moneda) !== 1) {
            $errores[] = 'la moneda "' . $datos[26] . '" no es un código ISO de tres letras.';
        } elseif ($moneda !== 'PEN' && (! is_numeric(trim($datos[27])) || (float) $datos[27] <= 0)) {
            $errores[] = 'el comprobante en moneda extranjera no tiene un tipo de cambio positivo.';
        }

        // Campos 14 a 25 del Anexo N.° 3: la suma debe dar el total.
        $errores = array_merge($errores, $this->cuadran(
            $datos,
            [13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24],
            25
        ));

        return $errores;
    }

    /**
     * @param  array<int, string>  $datos
     * @return array<int, string>
     */
    private function compras(array $datos): array
    {
        $errores = [];

        $tipo = trim($datos[6]);

        if (! $this->fechaValida($datos[4])) {
            $errores[] = 'la fecha de emisión es obligatoria y debe tener formato DD/MM/AAAA.';
        }

        if ($tipo === '') {
            $errores[] = 'falta el tipo de comprobante SUNAT.';
        }

        if (trim($datos[7]) === '') {
            $errores[] = 'falta la serie del comprobante.';
        }

        if (trim($datos[9]) === '') {
            $errores[] = 'falta el número del comprobante.';
        }

        if (trim($datos[13]) === '') {
            $errores[] = 'falta el nombre o razón social del proveedor.';
        }

        $tipoDoc = trim($datos[11]);
        $numeroDoc = trim($datos[12]);

        if ($tipoDoc === '' || $numeroDoc === '') {
            $errores[] = 'falta el documento de identidad del proveedor.';
        } elseif ($tipoDoc === '6' && strlen($numeroDoc) !== 11) {
            $errores[] = 'el RUC del proveedor debe tener 11 dígitos.';
        }

        if (in_array($tipo, ['07', '08'], true)) {
            if (! $this->fechaValida($datos[27])
                || trim($datos[28]) === ''
                || trim($datos[29]) === ''
                || trim($datos[31]) === '') {
                $errores[] = 'la nota no tiene el comprobante de referencia completo (fecha, tipo, serie y número).';
            }
        }

        $moneda = strtoupper(trim($datos[25]));

        if (preg_match('/^[A-Z]{3}$/', $moneda) !== 1) {
            $errores[] = 'la moneda "' . $datos[25] . '" no es un código ISO de tres letras.';
        } elseif ($moneda !== 'PEN' && (! is_numeric(trim($datos[26])) || (float) $datos[26] <= 0)) {
            $errores[] = 'el comprobante en moneda extranjera no tiene un tipo de cambio positivo.';
        }

        // Campos 15 a 24 del Anexo N.° 11. Las operaciones gratuitas no suman
        // al total, por eso también se acepta el total sin las no gravadas.
        $errores = array_merge($errores, $this->cuadran(
            $datos,
            [14, 15, 16, 17, 18, 19, 20, 21, 22, 23],
            24,
            [20]
        ));

        return $errores;
    }

    /**
     * Documento de identidad del cliente: la factura exige RUC, la boleta lo
     * lleva completo solo desde S/ 700 y las notas siempre lo necesitan.
     *
     * @param  array<int, string>  $datos
     * @return array<int, string>
     */
    private function documentoCliente(array $datos, string $tipo, float $total): array
    {
        $tipoDoc = trim($datos[10]);
        $numeroDoc = trim($datos[11]);

        if ($tipoDoc === '' || $numeroDoc === '') {
            if ($tipo === '03' && $total < 700) {
                return [];
            }

            return ['falta el documento de identidad del cliente.'];
        }

        if ($tipo === '01' && $tipoDoc !== '6') {
            return ['la factura exige el RUC del cliente como documento de identidad.'];
        }

        if ($tipoDoc === '6' && strlen($numeroDoc) !== 11) {
            return ['el RUC del cliente debe tener 11 dígitos.'];
        }

        if ($tipoDoc === '1' && strlen($numeroDoc) !== 8) {
            return ['el DNI del cliente debe tener 8 dígitos.'];
        }

        return [];
    }

    /**
     * @param  array<int, string>  $datos
     * @param  array<int, int>  $campos
     * @param  array<int, int>  $sinGravadas  Campos que se pueden descontar de la suma (RCE).
     * @return array<int, string>
     */
    private function cuadran(array $datos, array $campos, int $totalIdx, array $sinGravadas = []): array
    {
        $errores = [];
        $suma = 0.0;
        $completos = true;

        foreach ($campos as $indice) {
            $valor = trim($datos[$indice]);

            if ($valor === '' || ! is_numeric($valor)) {
                $errores[] = 'el importe del campo ' . ($indice + 1) . ' está vacío o no es numérico.';
                $completos = false;

                continue;
            }

            $suma += (float) $valor;
        }

        $total = trim($datos[$totalIdx]);

        if ($total === '' || ! is_numeric($total)) {
            $errores[] = 'el importe total está vacío o no es numérico.';

            return $errores;
        }

        if (! $completos) {
            return $errores;
        }

        $total = (float) $total;
        $cuadra = abs($suma - $total) <= self::TOLERANCIA;

        if (! $cuadra && $sinGravadas !== []) {
            $descuento = 0.0;

            foreach ($sinGravadas as $indice) {
                if (is_numeric(trim($datos[$indice]))) {
                    $descuento += (float) $datos[$indice];
                }
            }

            $cuadra = abs(($suma - $descuento) - $total) <= self::TOLERANCIA;
        }

        if (! $cuadra) {
            $errores[] = 'los importes no cuadran: la suma de bases, IGV, ISC y otros tributos ('
                . number_format($suma, 2, '.', '') . ') no coincide con el total ('
                . number_format($total, 2, '.', '') . ').';
        }

        return $errores;
    }

    private function fechaValida(string $fecha): bool
    {
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', trim($fecha), $partes) !== 1) {
            return false;
        }

        return checkdate((int) $partes[2], (int) $partes[1], (int) $partes[3]);
    }
}
