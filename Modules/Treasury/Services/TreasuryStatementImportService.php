<?php

namespace Modules\Treasury\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryReconciliationItem;
use Modules\Treasury\Entities\TreasuryStatementImport;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Exceptions\TreasuryOperationException;

/**
 * Importación de extractos bancarios para la conciliación.
 *
 * El flujo es en dos pasos y nunca adivina en silencio:
 *  1. `preview` lee el archivo, propone el mapeo de columnas según los
 *     encabezados y devuelve una muestra interpretada.
 *  2. `confirm` re-lee el archivo con el mapeo que el usuario aprobó, empareja
 *     cada línea con su movimiento del libro (referencia, monto y fecha) y
 *     registra como partida del extracto lo que no tiene par.
 *
 * No crea movimientos contables nuevos: las líneas que el libro no tiene se
 * convierten en partidas que explican la diferencia, tal como exige el cierre.
 */
class TreasuryStatementImportService
{
    private const ALLOWED = ['csv', 'txt', 'xlsx', 'xls', 'ofx', 'qfx'];

    /** Puntaje mínimo para considerar que dos líneas son la misma operación. */
    private const MATCH_THRESHOLD = 65;

    public function __construct(
        private TreasuryStatementParser $parser,
        private TreasuryReconciliationService $reconciliation,
        private TreasuryAuditService $audit,
    ) {
    }

    /**
     * Primera lectura: cabeceras, mapeo sugerido y muestra de filas.
     *
     * @return array<string, mixed>
     */
    public function preview(UploadedFile $file, TreasuryAccount $account, ?array $mappingOverride = null): array
    {
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->extension()));

        if (! in_array($extension, self::ALLOWED, true)) {
            throw new TreasuryOperationException('Formato no soportado. Sube el extracto en CSV, Excel (.xlsx) u OFX.');
        }

        $parsed = $this->parser->parse($file->getRealPath(), $extension);
        // El usuario puede corregir el mapeo y volver a leer antes de aplicar.
        $mapping = $mappingOverride !== null
            ? $this->coerceMapping($mappingOverride)
            : $this->parser->detectMapping($parsed['headers']);

        $token = $this->store($file, $extension);
        $sample = array_slice($parsed['rows'], 0, 25);
        $rows = $this->parser->normalizeRows($sample, $mapping);

        return [
            'token' => $token,
            'file_name' => $file->getClientOriginalName(),
            'format' => $parsed['format'],
            'delimiter' => $parsed['delimiter'],
            'headers' => $parsed['headers'],
            'total' => $parsed['total'],
            'mapping' => $mapping,
            'fields' => TreasuryStatementParser::FIELDS,
            'rows' => $rows,
            'invalid' => count(array_filter($rows, fn (array $row) => $row['error'] !== null)),
            'account' => [
                'id' => $account->id,
                'label' => $account->displayName(),
                'currency' => strtoupper((string) ($account->currency_type_id ?: 'PEN')) ?: 'PEN',
            ],
        ];
    }

    /**
     * Aplica el mapeo confirmado: empareja y guarda partidas.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function confirm(array $data, TreasuryAccount $account, ?int $userId = null): array
    {
        $token = (string) ($data['token'] ?? '');

        if (preg_match('/^[0-9a-fA-F-]{36}\.(csv|txt|xlsx|xls|ofx|qfx)$/', $token) !== 1) {
            throw new TreasuryOperationException('La lectura del extracto expiró. Vuelve a subir el archivo.');
        }

        $path = $this->directory() . DIRECTORY_SEPARATOR . $token;

        if (! File::exists($path)) {
            throw new TreasuryOperationException('No se encontró el extracto cargado. Vuelve a subirlo.');
        }

        $until = Carbon::parse((string) $data['until'])->toDateString();
        $mapping = $this->cleanMapping((array) ($data['mapping'] ?? []));
        $parsed = $this->parser->parse($path, strtolower(pathinfo($path, PATHINFO_EXTENSION)));
        $rows = $this->parser->normalizeRows($parsed['rows'], $mapping);

        $valid = array_values(array_filter($rows, fn (array $row) => $row['error'] === null));

        if ($valid === []) {
            throw new TreasuryOperationException('Ninguna fila del extracto se pudo interpretar con ese mapeo. Revisa las columnas.');
        }

        $import = TreasuryStatementImport::create([
            'treasury_account_id' => $account->id,
            'file_name' => (string) ($data['file_name'] ?? basename($token)),
            'format' => $parsed['format'],
            'delimiter' => $parsed['delimiter'],
            'rows_total' => count($valid),
            'mapping' => $mapping,
            'created_by' => $userId,
        ]);

        $matching = $this->match($account, $valid, $until);
        $created = 0;

        foreach ($matching['unmatched'] as $line) {
            $this->reconciliation->addItem(
                $account,
                $until,
                mb_substr($line['description'] ?: 'Movimiento del extracto', 0, 190),
                (float) $line['amount'],
                $line['type'] === 'income'
                    ? TreasuryReconciliationItem::TYPE_INCOME
                    : TreasuryReconciliationItem::TYPE_EXPENSE,
                $userId,
                $line['reference'] ?: null,
                'import',
                $import->id,
            );

            $created++;
        }

        $summary = [
            'rows_total' => count($valid),
            'rows_matched' => count($matching['matched']),
            'rows_created' => $created,
            'book_unmatched' => count($matching['book_unmatched']),
            'lines' => $matching['lines'],
            'book_unmatched_lines' => $matching['book_unmatched'],
        ];

        $import->fill([
            'rows_matched' => count($matching['matched']),
            'rows_created' => $created,
            'summary' => $summary,
        ])->save();

        // El extracto puede traer el N° de operación que el libro todavía no
        // tenía (movimientos creados cuando la columna se mapeaba como
        // referencia): se completa en el movimiento ya emparejado.
        $this->backfillOperationNumbers($import);

        $this->audit->log('statement_import', $import->id, 'imported', [], [
            'account' => $account->displayName(),
            'file' => $import->file_name,
            'rows' => count($valid),
            'matched' => count($matching['matched']),
            'created' => $created,
        ]);

        return [
            'import_id' => $import->id,
            'rows_total' => count($valid),
            'rows_matched' => count($matching['matched']),
            'rows_created' => $created,
            'book_unmatched' => count($matching['book_unmatched']),
            'matched_transaction_ids' => array_values(array_filter(array_column($matching['matched'], 'transaction_id'))),
            'lines' => $matching['lines'],
            'book_unmatched_lines' => $matching['book_unmatched'],
        ];
    }

    /**
     * Completa el N° de operación de los movimientos que el extracto ya trae
     * emparejados pero que se registraron sin número.
     *
     * El vínculo se guarda en el resumen de la importación; el movimiento del
     * libro es el mismo, así que solo se le agrega el dato para que el número
     * se vea en Movimientos, Conciliación y el resto del módulo.
     *
     * @return int Cantidad de movimientos actualizados.
     */
    public function backfillOperationNumbers(?TreasuryStatementImport $import = null): int
    {
        $imports = $import
            ? collect([$import])
            : TreasuryStatementImport::query()->orderBy('id')->get();

        $updated = 0;

        foreach ($imports as $statementImport) {
            $lines = array_values((array) (($statementImport->summary ?? [])['lines'] ?? []));

            // Números de operación que trae este extracto, indexados por su
            // forma normalizada (sin ceros a la izquierda ni separadores).
            $numbers = [];
            $linkedIds = [];

            foreach ($lines as $line) {
                $number = trim((string) ($line['operation_number'] ?? ''));

                if ($number !== '') {
                    $numbers[$this->normalizeReference($number)] = $number;
                }

                $transactionId = (int) ($line['transaction_id'] ?? $line['created_transaction_id'] ?? 0);

                if ($transactionId > 0) {
                    $linkedIds[$transactionId] = true;
                }
            }

            foreach (array_keys($linkedIds) as $transactionId) {
                $transaction = TreasuryTransaction::query()->find($transactionId);

                if (! $transaction || trim((string) $transaction->operation_number) !== '') {
                    continue;
                }

                // Manda la referencia del movimiento: si el extracto trae ese
                // número, ese es su N° de operación. Dos líneas iguales (mismo
                // día y monto) pueden haberse vinculado cruzadas, así que el
                // vínculo solo no basta.
                $reference = $this->normalizeReference((string) $transaction->reference);
                $number = $reference !== '' ? ($numbers[$reference] ?? null) : null;

                // Sin referencia en el libro, se usa el número de la línea que
                // lo emparejó.
                if ($number === null) {
                    foreach ($lines as $line) {
                        $linkedId = (int) ($line['transaction_id'] ?? $line['created_transaction_id'] ?? 0);
                        $candidate = trim((string) ($line['operation_number'] ?? ''));

                        if ($linkedId === $transaction->id && $candidate !== '') {
                            $number = $candidate;
                            break;
                        }
                    }
                }

                // «00000000» es lo que pone el banco cuando no hay número: no
                // sirve como identificador y no se guarda.
                if ($number === null || trim($number, '0') === '') {
                    continue;
                }

                // Un N° de operación no puede estar en dos movimientos de la
                // misma cuenta: si ya lo usa otro, no se toca.
                $duplicated = TreasuryTransaction::query()
                    ->where('treasury_account_id', $transaction->treasury_account_id)
                    ->whereNull('voided_at')
                    ->where('id', '!=', $transaction->id)
                    ->where('operation_number', $number)
                    ->exists();

                if ($duplicated) {
                    continue;
                }

                $transaction->operation_number = mb_substr($number, 0, 60);
                $transaction->save();
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Normaliza una referencia para compararla: minúsculas y solo letras y
     * números.
     */
    private function normalizeReference(string $value): string
    {
        $normalized = preg_replace('/[^a-z0-9]/i', '', mb_strtolower($value)) ?? '';

        if ($normalized === '') {
            return '';
        }

        // Los ceros a la izquierda no cambian el número (00002707 = 2707),
        // pero un número hecho solo de ceros se conserva tal cual.
        $trimmed = ltrim($normalized, '0');

        return $trimmed === '' ? $normalized : $trimmed;
    }

    /**
     * Descarta el par recomendado de una línea del extracto.
     *
     * Cuando el encargado sabe que el movimiento del libro y la línea del banco
     * son operaciones distintas (mismo día y monto, por ejemplo), el par se
     * deshace sin tocar el movimiento del libro: la línea vuelve a quedar «sin
     * par» para registrarla como un movimiento nuevo. El vínculo descartado se
     * conserva en el resumen para la auditoría.
     *
     * @throws TreasuryOperationException
     */
    public function dismissMatch(TreasuryStatementImport $import, int $lineIndex, ?int $userId = null): void
    {
        $summary = $import->summary ?? [];
        $lines = array_values($summary['lines'] ?? []);

        if (! isset($lines[$lineIndex])) {
            throw new TreasuryOperationException('La línea del extracto ya no existe. Vuelve a importarlo.');
        }

        $line = $lines[$lineIndex];
        $transactionId = $line['transaction_id'] ?? null;

        if (! $transactionId) {
            throw new TreasuryOperationException('Esta línea no tiene un par recomendado que descartar.');
        }

        // Con el mismo N° de operación no hay duda: es el mismo movimiento y no
        // se puede deshacer el par para registrarlo otra vez.
        $linked = TreasuryTransaction::find((int) $transactionId);

        if ($linked && $this->sameReference((string) ($line['operation_number'] ?? ''), (string) ($linked->operation_number ?? ''))) {
            throw new TreasuryOperationException(
                'El N° de operación coincide con el del libro: es el mismo movimiento, no se puede registrar otro.'
            );
        }

        unset($lines[$lineIndex]['transaction_id']);
        $lines[$lineIndex]['match_dismissed'] = true;
        $lines[$lineIndex]['dismissed_transaction_id'] = (int) $transactionId;
        $lines[$lineIndex]['score'] = null;

        $summary['lines'] = $lines;
        $import->summary = $summary;
        $import->save();

        $this->audit->log('statement_import', $import->id, 'match_dismissed', [], [
            'line_index' => $lineIndex,
            'bank_line' => [
                'date' => $line['date'] ?? null,
                'time' => $line['time'] ?? null,
                'amount' => $line['amount'] ?? null,
                'type' => $line['type'] ?? null,
            ],
            'transaction_id' => (int) $transactionId,
            'user_id' => $userId,
        ]);
    }

    /**
     * Empareja líneas del extracto con movimientos del libro.
     *
     * Solo son pares si coinciden monto y sentido (ingreso/egreso); la fecha
     * suma según cercanía. Puntaje: monto exacto (50), sentido (6), fecha
     * exacta (20) o cercana (12/6/2), referencia coincidente (40) y descripción
     * parecida (8). Se asigna de mejor a peor para que ninguna línea quede con
     * dos pares.
     *
     * @param  array<int, array<string, mixed>>  $lines
     * @return array{matched: array<int, array<string, mixed>>, unmatched: array<int, array<string, mixed>>, book_unmatched: array<int, array<string, mixed>>, lines: array<int, array<string, mixed>>}
     */
    private function match(TreasuryAccount $account, array $lines, string $until): array
    {
        $dates = array_filter(array_column($lines, 'date'));

        $from = $dates !== []
            ? Carbon::parse(min($dates))->subDays(7)->toDateString()
            : Carbon::parse($until)->subDays(45)->toDateString();

        // El N° de operación manda sobre la fecha: un movimiento con el mismo
        // número se busca aunque esté fuera del rango del extracto.
        $operations = array_values(array_filter(array_map(
            fn (array $line) => trim((string) ($line['operation_number'] ?? '')),
            $lines
        )));

        $candidates = TreasuryTransaction::query()
            ->active()
            ->where('treasury_account_id', $account->id)
            ->whereNull('reconciled_at')
            ->where(function ($query) use ($from, $until, $operations) {
                $query->where(function ($range) use ($from, $until) {
                    $range->whereDate('transaction_date', '>=', $from)
                        ->whereDate('transaction_date', '<=', $until);
                });

                if ($operations !== []) {
                    $query->orWhereIn('operation_number', $operations);
                }
            })
            ->orderBy('transaction_date')
            ->get();

        $pairs = [];

        foreach ($lines as $lineIndex => $line) {
            foreach ($candidates as $transaction) {
                $score = $this->score($line, $transaction);

                if ($score >= self::MATCH_THRESHOLD) {
                    $pairs[] = [
                        'line' => $lineIndex,
                        'transaction' => $transaction->id,
                        'score' => $score,
                    ];
                }
            }
        }

        usort($pairs, fn ($a, $b) => $b['score'] <=> $a['score']);

        $usedLines = [];
        $usedTransactions = [];
        $matched = [];

        foreach ($pairs as $pair) {
            if (isset($usedLines[$pair['line']]) || isset($usedTransactions[$pair['transaction']])) {
                continue;
            }

            $usedLines[$pair['line']] = true;
            $usedTransactions[$pair['transaction']] = true;
            $matched[$pair['line']] = [
                'transaction_id' => $pair['transaction'],
                'score' => $pair['score'],
            ];
        }

        $resultLines = [];
        $unmatched = [];

        foreach ($lines as $index => $line) {
            $pair = $matched[$index] ?? null;

            $resultLines[] = [
                'date' => $line['date'],
                'time' => $line['time'] ?? null,
                'description' => $line['description'],
                'amount' => $line['amount'],
                'type' => $line['type'],
                'reference' => $line['reference'],
                'operation_number' => $line['operation_number'] ?? null,
                'status' => $pair ? 'matched' : 'created',
                'transaction_id' => $pair['transaction_id'] ?? null,
                'score' => $pair['score'] ?? null,
            ];

            if (! $pair) {
                $unmatched[] = $line;
            }
        }

        $bookUnmatched = $candidates
            ->reject(fn (TreasuryTransaction $transaction) => isset($usedTransactions[$transaction->id]))
            ->map(fn (TreasuryTransaction $transaction) => [
                'id' => $transaction->id,
                'date' => $transaction->transaction_date?->format('Y-m-d'),
                'time' => $transaction->transaction_time,
                'operation_number' => $transaction->operation_number,
                'description' => $transaction->description ?: $transaction->reference,
                'amount' => (float) $transaction->amount,
                'type' => $transaction->type,
            ])
            ->values()
            ->all();

        return [
            'matched' => array_values($matched),
            'unmatched' => $unmatched,
            'book_unmatched' => $bookUnmatched,
            'lines' => $resultLines,
        ];
    }

    /**
     * Puntaje de parecido entre una línea del extracto y un movimiento.
     *
     * @param  array<string, mixed>  $line
     */
    private function score(array $line, TreasuryTransaction $transaction): int
    {
        $lineAmount = round((float) $line['amount'], 2);
        $bookAmount = round((float) $transaction->amount, 2);

        $lineOperation = trim((string) ($line['operation_number'] ?? ''));
        $bookOperation = trim((string) ($transaction->operation_number ?? ''));
        $sameOperation = $lineOperation !== '' && $bookOperation !== ''
            && $this->sameReference($lineOperation, $bookOperation);

        // Mismo N° de operación: es el mismo movimiento, aunque el monto o la
        // fecha no coincidan (el banco y el libro pueden diferir en el importe).
        if ($sameOperation) {
            return 100;
        }

        // El N° de operación identifica la operación en el banco: si ambos
        // existen y no coinciden, son movimientos distintos aunque el monto y
        // la fecha sean iguales.
        if ($lineOperation !== '' && $bookOperation !== '') {
            return 0;
        }

        if (abs($lineAmount - $bookAmount) > 0.05) {
            return 0;
        }

        // Un ingreso del banco nunca se empareja con un egreso del libro:
        // el sentido del movimiento es parte de la identidad de la línea.
        if (($line['type'] ?? '') !== $transaction->type) {
            return 0;
        }

        $score = 56;
        $days = abs(Carbon::parse($line['date'])->diffInDays($transaction->transaction_date));
        $score += $days === 0 ? 20 : ($days <= 1 ? 12 : ($days <= 3 ? 6 : ($days <= 5 ? 2 : -40)));

        if ($this->sameReference((string) ($line['reference'] ?? ''), (string) ($transaction->reference ?? ''))) {
            $score += 40;
        }

        if ($this->similarText((string) ($line['description'] ?? ''), (string) ($transaction->description ?? ''))) {
            $score += 8;
        }

        return $score;
    }

    /**
     * Compara referencias ignorando ceros a la izquierda y separadores.
     */
    private function sameReference(string $left, string $right): bool
    {
        $normalize = fn (string $value) => preg_replace('/[^a-z0-9]/i', '', mb_strtolower($value)) ?? '';

        $left = $normalize($left);
        $right = $normalize($right);

        if ($left === '' || $right === '') {
            return false;
        }

        return $left === $right || ltrim($left, '0') === ltrim($right, '0');
    }

    private function similarText(string $left, string $right): bool
    {
        if ($left === '' || $right === '') {
            return false;
        }

        similar_text(mb_strtolower($left), mb_strtolower($right), $percent);

        return $percent >= 60;
    }

    /**
     * Mapeo sin exigencias: se usa para la vista previa mientras el usuario
     * todavía está corrigiendo columnas.
     *
     * @param  array<string, mixed>  $mapping
     * @return array<string, int|null>
     */
    private function coerceMapping(array $mapping): array
    {
        $clean = [];

        foreach (array_keys(TreasuryStatementParser::FIELDS) as $field) {
            $value = $mapping[$field] ?? null;
            $clean[$field] = ($value === null || $value === '' || $value === -1) ? null : (int) $value;
        }

        return $clean;
    }

    /**
     * Normaliza el mapeo recibido del navegador y exige lo mínimo.
     *
     * @param  array<string, mixed>  $mapping
     * @return array<string, int|null>
     */
    private function cleanMapping(array $mapping): array
    {
        $clean = [];

        foreach (array_keys(TreasuryStatementParser::FIELDS) as $field) {
            $value = $mapping[$field] ?? null;
            $clean[$field] = ($value === null || $value === '' || $value === -1) ? null : (int) $value;
        }

        if ($clean['fecha'] === null) {
            throw new TreasuryOperationException('Indica qué columna tiene la fecha de cada movimiento.');
        }

        if ($clean['monto'] === null && $clean['cargo'] === null && $clean['abono'] === null) {
            throw new TreasuryOperationException('Indica la columna del monto, o las de cargo y abono.');
        }

        return $clean;
    }

    /**
     * Guarda el archivo con un nombre opaco y devuelve el token.
     */
    private function store(UploadedFile $file, string $extension): string
    {
        File::ensureDirectoryExists($this->directory());

        $this->cleanOldFiles();

        $token = Str::uuid()->toString() . '.' . $extension;
        File::copy($file->getRealPath(), $this->directory() . DIRECTORY_SEPARATOR . $token);

        return $token;
    }

    private function cleanOldFiles(): void
    {
        foreach (File::glob($this->directory() . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
            if (File::isFile($path) && File::lastModified($path) < now()->subDays(2)->getTimestamp()) {
                File::delete($path);
            }
        }
    }

    private function directory(): string
    {
        return storage_path('app/treasury-statements');
    }
}
