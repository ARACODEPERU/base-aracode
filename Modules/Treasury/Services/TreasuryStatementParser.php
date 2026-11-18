<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

/**
 * Lectura de extractos bancarios.
 *
 * Acepta CSV/TXT (cualquier delimitador habitual), Excel (xlsx/xls) y OFX.
 * Primero devuelve las cabeceras tal como vienen y una propuesta de mapeo; el
 * usuario la revisa en pantalla y el sistema recién entonces interpreta las
 * filas. Nunca se adivina el formato de fecha de una fila sin cabecera.
 */
class TreasuryStatementParser
{
    /** Campos que el usuario puede mapear. */
    public const FIELDS = [
        'fecha' => 'Fecha (o fecha y hora)',
        'hora' => 'Hora (si viene en otra columna)',
        'descripcion' => 'Descripción / concepto',
        'monto' => 'Monto (con signo)',
        'cargo' => 'Cargo / débito',
        'abono' => 'Abono / crédito',
        'referencia' => 'Referencia',
        'operacion' => 'N° de operación del banco',
        'saldo' => 'Saldo',
    ];

    /**
     * Lee el archivo y devuelve cabeceras y filas crudas.
     *
     * @return array{format: string, delimiter: ?string, headers: array<int, string>, rows: array<int, array<int, string>>, total: int}
     */
    public function parse(string $path, string $extension): array
    {
        $extension = strtolower($extension);

        $data = match ($extension) {
            'csv', 'txt' => $this->parseDelimited($path),
            'xlsx', 'xls' => $this->parseSpreadsheet($path),
            'ofx', 'qfx' => $this->parseOfx($path),
            default => throw new RuntimeException('Formato no soportado. Usa CSV, Excel (.xlsx) u OFX.'),
        };

        $data['rows'] = array_values(array_filter(
            $data['rows'],
            fn (array $row) => count(array_filter($row, fn ($cell) => trim((string) $cell) !== '')) > 0
        ));

        $data['total'] = count($data['rows']);

        if ($data['total'] === 0) {
            throw new RuntimeException('El archivo no tiene filas de movimientos legibles.');
        }

        return $data;
    }

    /**
     * Propuesta de mapeo a partir de las cabeceras.
     *
     * @param  array<int, string>  $headers
     * @return array<string, int|null>
     */
    public function detectMapping(array $headers): array
    {
        $normalized = array_map(fn ($header) => $this->normalizeHeader((string) $header), $headers);

        $rules = [
            'fecha' => ['fecha', 'fechavalor', 'fechaoperacion', 'fechadeposito', 'date', 'posted', 'dtposted', 'dia', 'fec'],
            'hora' => ['hora', 'horaoperacion', 'horatransaccion', 'horamovimiento', 'time', 'horaabono', 'horacargo'],
            'descripcion' => ['descripcion', 'concepto', 'detalle', 'glosa', 'operacion', 'movimiento', 'name', 'memo', 'detalleoperacion'],
            'monto' => ['monto', 'importe', 'amount', 'valor', 'total', 'trnamt'],
            'cargo' => ['cargo', 'debito', 'debe', 'debit', 'retiro', 'salida', 'pagado'],
            'abono' => ['abono', 'credito', 'haber', 'credit', 'deposito', 'entrada', 'recibido'],
            'referencia' => ['referencia', 'ref', 'nro', 'numero', 'documento', 'fitid', 'comprobante'],
            'operacion' => ['noperacion', 'nrooperacion', 'numerooperacion', 'operacionnro', 'codigooperacion', 'nrooperacionbanco', 'operationnumber', 'operacion', 'operacionbancaria'],
            'saldo' => ['saldo', 'balance', 'saldoactual', 'saldodisponible'],
        ];

        $mapping = [];

        foreach ($rules as $field => $aliases) {
            $mapping[$field] = $this->findHeader($normalized, $aliases);
        }

        // Si hay cargo/abono, el monto único estorba: se prefiere el desglose.
        if (($mapping['cargo'] !== null || $mapping['abono'] !== null) && $mapping['monto'] !== null) {
            $mapping['monto'] = null;
        }

        return $mapping;
    }

    /**
     * Interpreta las filas con el mapeo confirmado.
     *
     * @param  array<int, array<int, string>>  $rows
     * @param  array<string, int|null>  $mapping
     * @return array<int, array{date: string, time: ?string, description: string, amount: float, type: string, reference: ?string, operation_number: ?string, balance: ?float, error: ?string}>
     */
    public function normalizeRows(array $rows, array $mapping, ?int $limit = null): array
    {
        $result = [];
        $seen = 0;

        foreach ($rows as $row) {
            if ($limit !== null && $seen >= $limit) {
                break;
            }

            $seen++;

            $date = $this->value($row, $mapping['fecha'] ?? null);
            $hourColumn = $this->value($row, $mapping['hora'] ?? null);
            $description = $this->value($row, $mapping['descripcion'] ?? null);
            $reference = $this->value($row, $mapping['referencia'] ?? null);
            $operationNumber = $this->value($row, $mapping['operacion'] ?? null);
            $balanceRaw = $this->value($row, $mapping['saldo'] ?? null);

            $parsedDate = $this->parseDate($date);
            $entry = [
                'date' => $parsedDate ?? '',
                // La hora se toma de su columna si existe; si no, de la fecha.
                'time' => $this->parseTime($hourColumn) ?? $this->parseTime($date),
                'description' => trim($description),
                'amount' => 0.0,
                'type' => 'expense',
                'reference' => $reference !== '' ? mb_substr(trim($reference), 0, 120) : null,
                'operation_number' => $operationNumber !== '' ? mb_substr(trim($operationNumber), 0, 60) : null,
                'balance' => $balanceRaw !== '' ? $this->parseAmount($balanceRaw) : null,
                'error' => null,
            ];

            $cargo = $this->parseAmount($this->value($row, $mapping['cargo'] ?? null));
            $abono = $this->parseAmount($this->value($row, $mapping['abono'] ?? null));
            $monto = $this->parseAmount($this->value($row, $mapping['monto'] ?? null));

            if (abs($cargo) > 0 || abs($abono) > 0) {
                if (abs($cargo) > 0) {
                    $entry['amount'] = round(abs($cargo), 2);
                    $entry['type'] = 'expense';
                } else {
                    $entry['amount'] = round(abs($abono), 2);
                    $entry['type'] = 'income';
                }
            } elseif (abs($monto) > 0) {
                $entry['amount'] = round(abs($monto), 2);
                $entry['type'] = $monto < 0 ? 'expense' : 'income';
            }

            if (! $parsedDate) {
                $entry['error'] = 'Fecha no reconocida';
            } elseif ($entry['amount'] <= 0) {
                $entry['error'] = 'Monto vacío o cero';
            } elseif ($entry['description'] === '') {
                $entry['description'] = $entry['reference'] ?: 'Movimiento del extracto';
            }

            $result[] = $entry;
        }

        return $result;
    }

    /**
     * CSV/TXT con delimitador detectado por frecuencia.
     *
     * @return array{format: string, delimiter: ?string, headers: array<int, string>, rows: array<int, array<int, string>>, total: int}
     */
    private function parseDelimited(string $path): array
    {
        $content = $this->readText($path);
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $lines = array_values(array_filter($lines, fn ($line) => trim($line) !== ''));

        if (count($lines) === 0) {
            throw new RuntimeException('El archivo está vacío.');
        }

        $delimiter = $this->guessDelimiter($lines[0]);
        $rows = [];

        foreach ($lines as $line) {
            $rows[] = str_getcsv($line, $delimiter);
        }

        $headers = array_map(fn ($value) => trim((string) $value), array_shift($rows) ?? []);

        return [
            'format' => pathinfo($path, PATHINFO_EXTENSION) ?: 'csv',
            'delimiter' => $delimiter,
            'headers' => $headers,
            'rows' => $rows,
            'total' => count($rows),
        ];
    }

    /**
     * Excel: primera hoja y primera fila no vacía como cabecera.
     *
     * @return array{format: string, delimiter: ?string, headers: array<int, string>, rows: array<int, array<int, string>>, total: int}
     */
    private function parseSpreadsheet(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $matrix = $sheet->toArray(null, true, true, false);

        $headers = [];
        $rows = [];

        foreach ($matrix as $row) {
            $values = array_map(function ($value) {
                if ($value instanceof \DateTimeInterface) {
                    // Los extractos en Excel suelen traer fecha y hora en la misma celda.
                    return $value->format('H:i:s') === '00:00:00'
                        ? $value->format('Y-m-d')
                        : $value->format('Y-m-d H:i:s');
                }

                return trim((string) $value);
            }, $row);

            if (count(array_filter($values, fn ($value) => $value !== '')) === 0) {
                continue;
            }

            if ($headers === []) {
                $headers = $values;
                continue;
            }

            $rows[] = $values;

            if (count($rows) >= 6000) {
                break;
            }
        }

        $spreadsheet->disconnectWorksheets();

        return [
            'format' => strtolower(pathinfo($path, PATHINFO_EXTENSION)) ?: 'xlsx',
            'delimiter' => null,
            'headers' => $headers,
            'rows' => $rows,
            'total' => count($rows),
        ];
    }

    /**
     * OFX: se extraen los bloques STMTTRN.
     *
     * @return array{format: string, delimiter: ?string, headers: array<int, string>, rows: array<int, array<int, string>>, total: int}
     */
    private function parseOfx(string $path): array
    {
        $content = $this->readText($path);

        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/is', $content, $matches);

        if (empty($matches[1])) {
            throw new RuntimeException('El archivo OFX no tiene movimientos (STMTTRN).');
        }

        $rows = [];

        foreach ($matches[1] as $block) {
            $date = $this->ofxTag($block, 'DTPOSTED');
            $amount = $this->ofxTag($block, 'TRNAMT');
            $fitId = $this->ofxTag($block, 'FITID');
            $name = $this->ofxTag($block, 'NAME');
            $memo = $this->ofxTag($block, 'MEMO');

            $rows[] = [
                $date,
                trim($name !== '' ? $name : $memo),
                $amount,
                $fitId,
            ];
        }

        return [
            'format' => 'ofx',
            'delimiter' => null,
            'headers' => ['Fecha', 'Descripción', 'Monto', 'Referencia'],
            'rows' => $rows,
            'total' => count($rows),
        ];
    }

    private function ofxTag(string $block, string $tag): string
    {
        if (preg_match('/<' . $tag . '>\s*([^<\r\n]+)/i', $block, $match)) {
            return trim($match[1]);
        }

        return '';
    }

    private function readText(string $path): string
    {
        $content = (string) file_get_contents($path);

        // Los extractos suelen venir en Latin-1; se normaliza a UTF-8.
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = (string) mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        return preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
    }

    private function guessDelimiter(string $line): string
    {
        $candidates = [';' => substr_count($line, ';'), ',' => substr_count($line, ','), "\t" => substr_count($line, "\t"), '|' => substr_count($line, '|')];
        arsort($candidates);

        $delimiter = array_key_first($candidates);

        return $candidates[$delimiter] > 0 ? $delimiter : ',';
    }

    private function value(array $row, ?int $index): string
    {
        if ($index === null || ! array_key_exists($index, $row)) {
            return '';
        }

        return trim((string) $row[$index]);
    }

    private function normalizeHeader(string $header): string
    {
        $header = mb_strtolower(trim($header));
        $header = strtr($header, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u']);

        return preg_replace('/[^a-z0-9]/', '', $header) ?? '';
    }

    /**
     * Busca la cabecera que mejor coincide con los alias.
     *
     * @param  array<int, string>  $normalized
     * @param  array<int, string>  $aliases
     */
    private function findHeader(array $normalized, array $aliases): ?int
    {
        foreach ($normalized as $index => $header) {
            if ($header === '') {
                continue;
            }

            foreach ($aliases as $alias) {
                if ($header === $alias) {
                    return $index;
                }
            }
        }

        // Segunda pasada: coincidencia parcial (p. ej. «fecha operación»).
        foreach ($normalized as $index => $header) {
            foreach ($aliases as $alias) {
                if ($header !== '' && strlen($alias) >= 4 && str_contains($header, $alias)) {
                    return $index;
                }
            }
        }

        return null;
    }

    /**
     * Importe con separadores de miles y símbolos de moneda.
     */
    private function parseAmount(string $value): float
    {
        $value = trim($value);

        if ($value === '') {
            return 0.0;
        }

        $negative = str_contains($value, '(') && str_contains($value, ')');
        $value = preg_replace('/[^\d,.\-]/u', '', $value) ?? '';

        // Formato 1.234,56 (coma decimal) vs 1,234.56 (punto decimal).
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $lastComma = strrpos($value, ',');
            $lastDot = strrpos($value, '.');

            if ($lastComma > $lastDot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif (str_contains($value, ',')) {
            $parts = explode(',', $value);

            if (count($parts) === 2 && strlen(end($parts)) <= 2) {
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        }

        $amount = (float) $value;

        return $negative ? -abs($amount) : $amount;
    }

    /**
     * Hora del movimiento, si el extracto la trae.
     *
     * Acepta «14:35», «14:35:20», una fecha con hora («02/11/2026 14:35») y el
     * formato OFX («20261114143500»). Devuelve H:i:s o null.
     */
    private function parseTime(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // OFX: 20261114143500 (fecha y hora pegadas, sin separadores).
        if (preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $value, $match)) {
            $hour = (int) $match[4];
            $minute = (int) $match[5];
            $second = (int) $match[6];

            return ($hour < 24 && $minute < 60 && $second < 60)
                ? sprintf('%02d:%02d:%02d', $hour, $minute, $second)
                : null;
        }

        if (preg_match('/(?<!\d)(\d{1,2}):(\d{2})(?::(\d{2}))?(?!\d)/', $value, $match)) {
            $hour = (int) $match[1];
            $minute = (int) $match[2];
            $second = (int) ($match[3] ?? 0);

            return ($hour < 24 && $minute < 60 && $second < 60)
                ? sprintf('%02d:%02d:%02d', $hour, $minute, $second)
                : null;
        }

        return null;
    }

    /**
     * Fechas habituales de extractos (d/m/Y, Y-m-d, m/d/Y…) y OFX (20261114).
     */
    private function parseDate(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // OFX: 20261114120000[-5:EST]
        if (preg_match('/^(\d{4})(\d{2})(\d{2})/', $value, $match)) {
            $candidate = "{$match[1]}-{$match[2]}-{$match[3]}";

            if (checkdate((int) $match[2], (int) $match[3], (int) $match[1])) {
                return $candidate;
            }
        }

        // Serial de Excel.
        if (is_numeric($value) && (float) $value > 20000 && (float) $value < 80000) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                // Se sigue con los formatos de texto.
            }
        }

        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'd/m/y', 'd.m.Y', 'Y/m/d', 'm/d/Y', 'd/m/Y H:i', 'Y-m-d H:i:s', 'd/m/Y H:i:s'];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat('!' . $format, $value);
            $errors = \DateTime::getLastErrors();

            if ($date && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d');
            }
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            Log::warning('[Treasury] Fecha de extracto no reconocida', ['value' => $value]);

            return null;
        }
    }
}
