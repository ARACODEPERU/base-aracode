<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryReconciliation;
use Modules\Treasury\Entities\TreasuryReconciliationItem;
use Modules\Treasury\Entities\TreasuryStatementImport;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Exceptions\TreasuryOperationException;

/**
 * Conciliación bancaria con acta inmutable.
 *
 * El cierre congela el saldo del sistema a la fecha de corte, exige que la
 * diferencia esté explicada por las partidas pendientes (o dentro de la
 * tolerancia), marca las transacciones incluidas y guarda quién lo hizo y la
 * huella de lo conciliado. Esas transacciones quedan bloqueadas para siempre.
 */
class TreasuryReconciliationService
{
    public function __construct(
        private TreasuryLedgerService $ledger,
        private TreasuryAuditService $audit,
    ) {
    }

    /**
     * Datos para el borrador de conciliación de una cuenta a una fecha.
     *
     * @return array<string, mixed>
     */
    public function draft(TreasuryAccount $account, string $until): array
    {
        $systemBalance = $this->ledger->accountBalance($account, $until);

        $transactions = TreasuryTransaction::query()
            ->active()
            ->where('treasury_account_id', $account->id)
            ->whereDate('transaction_date', '<=', $until)
            ->whereNull('reconciled_at')
            ->with(['category', 'reconciliation'])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $items = TreasuryReconciliationItem::query()
            ->where('treasury_account_id', $account->id)
            ->whereDate('cutoff_date', $until)
            ->where('status', TreasuryReconciliationItem::STATUS_PENDING)
            ->orderBy('id')
            ->get();

        $incomeItems = round((float) $items->where('type', TreasuryReconciliationItem::TYPE_INCOME)->sum('amount'), 2);
        $expenseItems = round((float) $items->where('type', TreasuryReconciliationItem::TYPE_EXPENSE)->sum('amount'), 2);

        $adjustedSystem = round($systemBalance - $expenseItems + $incomeItems, 2);

        return [
            'account' => [
                'id' => $account->id,
                'label' => $account->displayName(),
                'currency' => strtoupper((string) ($account->currency_type_id ?: 'PEN')) ?: 'PEN',
            ],
            'until' => $until,
            'system_balance' => $systemBalance,
            'items' => $items->map(fn (TreasuryReconciliationItem $item) => [
                'id' => $item->id,
                'description' => $item->description,
                'amount' => (float) $item->amount,
                'type' => $item->type,
                'type_label' => $item->type === TreasuryReconciliationItem::TYPE_INCOME ? 'Ingreso del banco' : 'Cargo del banco',
            ])->all(),
            'items_income' => $incomeItems,
            'items_expense' => $expenseItems,
            'adjusted_system_balance' => $adjustedSystem,
            'transactions' => $transactions->map(fn (TreasuryTransaction $tx) => [
                'id' => $tx->id,
                'date' => $tx->transaction_date->format('d/m/Y'),
                'type' => $tx->type,
                'amount' => (float) $tx->amount,
                'currency' => $tx->currency ?: 'PEN',
                'reference' => $tx->reference,
                'description' => $tx->description,
                'category' => $tx->category?->name,
                'source' => $tx->source,
                'reconciled' => $tx->reconciled_at !== null,
            ])->all(),
            'status' => TreasuryReconciliation::query()
                ->where('treasury_account_id', $account->id)
                ->whereDate('cutoff_date', $until)
                ->first()?->status,
        ];
    }

    /**
     * Comparación del último extracto importado contra el libro.
     *
     * Toma el rango real de fechas del extracto, lo amplía unos días y
     * devuelve las líneas del banco con su par del libro (aceptado por
     * defecto cuando monto y fecha coinciden) y los movimientos del libro que
     * el extracto no trae.
     *
     * @return array<string, mixed>|null
     */
    public function statementComparison(TreasuryAccount $account, int $marginDays = 7): ?array
    {
        $import = TreasuryStatementImport::query()
            ->where('treasury_account_id', $account->id)
            ->latest('id')
            ->first();

        if (! $import) {
            return null;
        }

        $summary = $import->summary ?? [];
        $rawLines = array_values($summary['lines'] ?? []);

        if ($rawLines === []) {
            return null;
        }

        $dates = array_values(array_filter(array_map(fn (array $line) => $line['date'] ?? null, $rawLines)));
        $from = $dates !== [] ? Carbon::parse(min($dates))->subDays($marginDays)->toDateString() : null;
        $to = $dates !== [] ? Carbon::parse(max($dates))->addDays($marginDays)->toDateString() : null;

        // El N° de operación manda sobre el rango de fechas: un movimiento con
        // el mismo número es el mismo movimiento, aunque el extracto lo traiga
        // con otra fecha.
        $operations = array_values(array_filter(array_map(
            fn (array $line) => trim((string) ($line['operation_number'] ?? '')),
            $rawLines
        )));

        $transactions = TreasuryTransaction::query()
            ->active()
            ->where('treasury_account_id', $account->id)
            ->where(function ($query) use ($from, $to, $operations) {
                $query->where(function ($range) use ($from, $to) {
                    $range->when($from, fn ($inner) => $inner->whereDate('transaction_date', '>=', $from))
                        ->when($to, fn ($inner) => $inner->whereDate('transaction_date', '<=', $to));
                });

                if ($operations !== []) {
                    $query->orWhereIn('operation_number', $operations);
                }
            })
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $byId = $transactions->keyBy('id');

        $lines = [];

        foreach ($rawLines as $index => $line) {
            $pairedId = $line['transaction_id'] ?? null;
            $createdId = $line['created_transaction_id'] ?? null;
            $paired = $pairedId ? $byId->get((int) $pairedId) : null;
            $created = $createdId ? $byId->get((int) $createdId) : null;
            $linked = $paired ?: $created;

            // Extractos importados antes del N° de operación: si el número
            // coincide con un movimiento del libro, es el mismo movimiento y se
            // muestra emparejado aunque el resumen no guarde el vínculo.
            $operationMatch = null;

            if (! $linked && trim((string) ($line['operation_number'] ?? '')) !== '') {
                $operationMatch = $transactions->first(
                    fn (TreasuryTransaction $transaction) => $this->sameOperation(
                        (string) $line['operation_number'],
                        $transaction->operation_number
                    )
                );

                if ($operationMatch) {
                    $linked = $operationMatch;
                }
            }

            // En el resumen viejo `status` solo decía si se creó una partida:
            // el par real se reconoce por los ids guardados.
            $status = 'pending';

            if ($created) {
                $status = 'created';
            } elseif ($paired || $operationMatch) {
                $status = 'accepted';
            }

            $lines[] = [
                'index' => $index,
                'date' => $line['date'],
                'time' => $line['time'] ?? null,
                'description' => $line['description'] ?: 'Movimiento del extracto',
                'reference' => $line['reference'] ?? null,
                'operation_number' => $line['operation_number'] ?? null,
                'amount' => (float) $line['amount'],
                'type' => ($line['type'] ?? '') === 'income' ? 'income' : 'expense',
                'status' => $status,
                'operation_match' => $operationMatch !== null,
                'score' => $line['score'] ?? null,
                'transaction' => $linked ? [
                    'id' => $linked->id,
                    'date' => $linked->transaction_date?->format('d/m/Y'),
                    'time' => $linked->transaction_time,
                    'operation_number' => $linked->operation_number,
                    'description' => $linked->description ?: $linked->reference,
                    'amount' => (float) $linked->amount,
                    'type' => $linked->type,
                ] : null,
            ];
        }

        $linkedIds = array_values(array_filter(array_map(
            fn (array $line) => $line['transaction']['id'] ?? null,
            $lines
        )));

        $book = $transactions->map(fn (TreasuryTransaction $transaction) => [
            'id' => $transaction->id,
            'date' => $transaction->transaction_date?->format('d/m/Y'),
            'time' => $transaction->transaction_time,
            'operation_number' => $transaction->operation_number,
            'raw_date' => $transaction->transaction_date?->toDateString(),
            'description' => $transaction->description ?: $transaction->reference,
            'amount' => (float) $transaction->amount,
            'type' => $transaction->type,
            'matched' => in_array($transaction->id, $linkedIds, true),
        ])->all();

        return [
            'import_id' => $import->id,
            'file_name' => $import->file_name,
            'imported_at' => $import->created_at?->format('d/m/Y H:i'),
            'margin_days' => $marginDays,
            'from' => $from,
            'to' => $to,
            'lines' => $lines,
            'book' => $book,
            'book_unmatched' => array_values(array_filter($book, fn (array $row) => ! $row['matched'])),
            'accepted' => count(array_filter($lines, fn (array $line) => $line['status'] === 'accepted')),
            'created' => count(array_filter($lines, fn (array $line) => $line['status'] === 'created')),
            'pending' => count(array_filter($lines, fn (array $line) => $line['status'] === 'pending')),
        ];
    }

    /**
     * Registra como movimiento del libro una línea del extracto que no tenía
     * par. Deja de ser una partida de ajuste: ahora es un movimiento real y la
     * línea queda vinculada a él.
     *
     * @throws TreasuryOperationException
     */
    public function createMovementFromStatement(
        TreasuryStatementImport $import,
        int $lineIndex,
        ?int $userId = null,
    ): TreasuryTransaction {
        $account = TreasuryAccount::query()->active()->find($import->treasury_account_id);

        if (! $account) {
            throw new TreasuryOperationException('La cuenta del extracto ya no está activa.');
        }

        $summary = $import->summary ?? [];
        $lines = array_values($summary['lines'] ?? []);

        if (! isset($lines[$lineIndex])) {
            throw new TreasuryOperationException('La línea del extracto ya no existe. Vuelve a importarlo.');
        }

        $line = $lines[$lineIndex];

        if (! empty($line['transaction_id']) || ! empty($line['created_transaction_id'])) {
            throw new TreasuryOperationException('Esta línea del extracto ya tiene un movimiento vinculado.');
        }

        $amount = round(abs((float) ($line['amount'] ?? 0)), 2);

        if ($amount <= 0) {
            throw new TreasuryOperationException('La línea no tiene un monto válido.');
        }

        // Si el N° de operación ya está en la cuenta, es el mismo movimiento:
        // registrarlo otra vez duplicaría la operación del banco.
        $this->ledger->assertOperationNumberAvailable($account->id, $line['operation_number'] ?? null);

        $type = ($line['type'] ?? '') === TreasuryTransaction::TYPE_INCOME
            ? TreasuryTransaction::TYPE_INCOME
            : TreasuryTransaction::TYPE_EXPENSE;
        $date = Carbon::parse((string) ($line['date'] ?? now()->toDateString()))->toDateString();

        $transaction = TreasuryTransaction::create([
            'treasury_account_id' => $account->id,
            'transaction_date' => $date,
            // Si el extracto trae la hora, se conserva en el movimiento.
            'transaction_time' => $line['time'] ?? null,
            'type' => $type,
            'amount' => $amount,
            'currency' => strtoupper((string) ($account->currency_type_id ?: 'PEN')) ?: 'PEN',
            'amount_base' => $amount,
            'reference' => $line['reference'] ?? null,
            'operation_number' => $line['operation_number'] ?? null,
            'description' => mb_substr((string) ($line['description'] ?: 'Movimiento del extracto'), 0, 190),
            'source' => TreasuryTransaction::SOURCE_STATEMENT,
            'user_id' => $userId,
        ]);

        // La partida que ajustaba la diferencia ya no hace falta: el banco y el
        // libro ahora coinciden con un movimiento real.
        $item = TreasuryReconciliationItem::query()
            ->where('statement_import_id', $import->id)
            ->where('status', TreasuryReconciliationItem::STATUS_PENDING)
            ->where('treasury_account_id', $account->id)
            ->where('type', $type)
            ->where('amount', $amount)
            ->where('description', mb_substr((string) ($line['description'] ?: 'Movimiento del extracto'), 0, 190))
            ->first();

        if ($item) {
            $this->removeItem($item, $userId);
        }

        $lines[$lineIndex]['created_transaction_id'] = $transaction->id;
        $summary['lines'] = $lines;
        $import->summary = $summary;
        $import->save();

        $this->audit->log('statement_import', $import->id, 'movement_created', [], [
            'transaction_id' => $transaction->id,
            'account' => $account->displayName(),
            'date' => $date,
            'amount' => $amount,
            'type' => $type,
            'description' => $line['description'] ?? null,
        ]);

        return $transaction;
    }

    /**
     * Registra varias líneas del extracto seleccionadas en una sola operación.
     *
     * Todo o nada: si una de las líneas ya tiene movimiento o no tiene un
     * monto válido, no se registra ninguna y se informa el motivo.
     *
     * @param  array<int, int|string>  $lineIndexes
     * @return array<int, TreasuryTransaction>
     *
     * @throws TreasuryOperationException
     */
    public function createMovementsFromStatement(
        TreasuryStatementImport $import,
        array $lineIndexes,
        ?int $userId = null,
    ): array {
        $indexes = array_values(array_unique(array_map('intval', $lineIndexes)));

        if ($indexes === []) {
            throw new TreasuryOperationException('Selecciona al menos una línea del extracto.');
        }

        return DB::transaction(function () use ($import, $indexes, $userId) {
            $created = [];

            foreach ($indexes as $index) {
                $created[] = $this->createMovementFromStatement($import, $index, $userId);
            }

            return $created;
        });
    }

    /**
     * Compara N° de operación ignorando espacios, ceros a la izquierda y
     * separadores.
     */
    private function sameOperation(?string $left, ?string $right): bool
    {
        $normalize = fn (?string $value) => preg_replace('/[^a-z0-9]/i', '', mb_strtolower((string) $value)) ?? '';

        $left = $normalize($left);
        $right = $normalize($right);

        if ($left === '' || $right === '') {
            return false;
        }

        return $left === $right || ltrim($left, '0') === ltrim($right, '0');
    }

    /**
     * Cierra la conciliación. Idempotente: si ya existe un acta cerrada para
     * esa cuenta y fecha, la devuelve sin modificar nada.
     *
     * @param  array<int, int>  $transactionIds
     */
    public function close(
        TreasuryAccount $account,
        string $until,
        float $statementBalance,
        array $transactionIds,
        ?int $userId = null,
        ?string $notes = null,
    ): TreasuryReconciliation {
        $existing = TreasuryReconciliation::query()
            ->where('treasury_account_id', $account->id)
            ->whereDate('cutoff_date', $until)
            ->first();

        if ($existing?->isClosed()) {
            return $existing;
        }

        return DB::transaction(function () use ($account, $until, $statementBalance, $transactionIds, $userId, $notes, $existing) {
            // Bloquea la cuenta para que dos cierres simultáneos no se cuelen.
            TreasuryAccount::query()->lockForUpdate()->findOrFail($account->id);

            $systemBalance = $this->ledger->accountBalance($account, $until);

            $items = TreasuryReconciliationItem::query()
                ->where('treasury_account_id', $account->id)
                ->whereDate('cutoff_date', $until)
                ->where('status', TreasuryReconciliationItem::STATUS_PENDING)
                ->lockForUpdate()
                ->get();

            $incomeItems = round((float) $items->where('type', TreasuryReconciliationItem::TYPE_INCOME)->sum('amount'), 2);
            $expenseItems = round((float) $items->where('type', TreasuryReconciliationItem::TYPE_EXPENSE)->sum('amount'), 2);
            $adjustedSystem = round($systemBalance - $expenseItems + $incomeItems, 2);
            $difference = round($adjustedSystem - $statementBalance, 2);
            $tolerance = (float) config('treasury.reconciliation_tolerance', 0.01);

            if (abs($difference) > $tolerance) {
                throw new TreasuryOperationException(
                    'La conciliación no cuadra: diferencia de ' . number_format($difference, 2, '.', '')
                    . '. Registra las partidas del extracto que faltan o revisa el saldo del banco.'
                );
            }

            $transactions = TreasuryTransaction::query()
                ->active()
                ->where('treasury_account_id', $account->id)
                ->whereDate('transaction_date', '<=', $until)
                ->whereNull('reconciled_at')
                ->when($transactionIds !== [], fn ($query) => $query->whereIn('id', $transactionIds))
                ->lockForUpdate()
                ->get();

            $reconciliation = $existing ?: new TreasuryReconciliation([
                'treasury_account_id' => $account->id,
                'cutoff_date' => $until,
            ]);

            $reconciliation->fill([
                'statement_balance' => $statementBalance,
                'system_balance' => $systemBalance,
                'difference' => $difference,
                'currency' => strtoupper((string) ($account->currency_type_id ?: 'PEN')) ?: 'PEN',
                'status' => TreasuryReconciliation::STATUS_CLOSED,
                'notes' => $notes,
                'created_by' => $reconciliation->created_by ?: $userId,
                'closed_by' => $userId,
                'closed_at' => now(),
            ]);

            $reconciliation->snapshot_hash = $this->hash($account, $until, $statementBalance, $systemBalance, $transactions, $items);
            $reconciliation->save();

            foreach ($transactions as $transaction) {
                // update directo: el modelo bloquea cambios, pero aquí es el
                // propio cierre el que sella la transacción.
                TreasuryTransaction::query()->whereKey($transaction->id)->update([
                    'reconciled_at' => now(),
                    'treasury_reconciliation_id' => $reconciliation->id,
                ]);
            }

            foreach ($items as $item) {
                $item->treasury_reconciliation_id = $reconciliation->id;
                $item->save();
            }

            $this->audit->log('reconciliation', $reconciliation->id, 'closed', [], [
                'account' => $account->displayName(),
                'cutoff_date' => $until,
                'statement_balance' => $statementBalance,
                'system_balance' => $systemBalance,
                'difference' => $difference,
                'transactions' => $transactions->pluck('id')->all(),
                'snapshot_hash' => $reconciliation->snapshot_hash,
            ]);

            return $reconciliation;
        });
    }

    /**
     * Agrega una partida del extracto que no está en el libro. Es lo que
     * permite explicar la diferencia antes de cerrar.
     */
    public function addItem(
        TreasuryAccount $account,
        string $cutoffDate,
        string $description,
        float $amount,
        string $type,
        ?int $userId = null,
        ?string $reference = null,
        string $source = 'manual',
        ?int $importId = null,
    ): TreasuryReconciliationItem {
        $closed = TreasuryReconciliation::query()
            ->where('treasury_account_id', $account->id)
            ->whereDate('cutoff_date', $cutoffDate)
            ->where('status', TreasuryReconciliation::STATUS_CLOSED)
            ->exists();

        if ($closed) {
            throw new TreasuryOperationException('La conciliación de ese día ya está cerrada: no admite más partidas.');
        }

        $item = TreasuryReconciliationItem::create([
            'treasury_account_id' => $account->id,
            'cutoff_date' => $cutoffDate,
            'description' => $description,
            'reference' => $reference,
            'amount' => round(abs($amount), 2),
            'type' => $type,
            'source' => $source,
            'statement_import_id' => $importId,
            'status' => TreasuryReconciliationItem::STATUS_PENDING,
            'created_by' => $userId,
        ]);

        $this->audit->log('reconciliation_item', $item->id, 'created', [], [
            'account' => $account->displayName(),
            'cutoff_date' => $cutoffDate,
            'description' => $description,
            'amount' => $item->amount,
            'type' => $type,
            'reference' => $reference,
            'source' => $source,
        ]);

        return $item;
    }

    public function removeItem(TreasuryReconciliationItem $item, ?int $userId = null): bool
    {
        if ($item->treasury_reconciliation_id) {
            throw new TreasuryOperationException('La partida pertenece a una conciliación cerrada: no se puede borrar.');
        }

        $this->audit->log('reconciliation_item', $item->id, 'deleted', [
            'description' => $item->description,
            'amount' => (float) $item->amount,
        ], []);

        return (bool) $item->delete();
    }

    /**
     * Historial de actas de la cuenta.
     *
     * @return array<int, array<string, mixed>>
     */
    public function history(TreasuryAccount $account, int $limit = 24): array
    {
        return TreasuryReconciliation::query()
            ->where('treasury_account_id', $account->id)
            ->with(['closer'])
            ->orderByDesc('cutoff_date')
            ->limit($limit)
            ->get()
            ->map(fn (TreasuryReconciliation $row) => [
                'id' => $row->id,
                'cutoff_date' => $row->cutoff_date->format('d/m/Y'),
                'statement_balance' => (float) $row->statement_balance,
                'system_balance' => (float) $row->system_balance,
                'difference' => (float) $row->difference,
                'currency' => $row->currency,
                'status' => $row->status,
                'closed_by' => $row->closer?->name,
                'closed_at' => $row->closed_at?->format('d/m/Y H:i'),
                'snapshot_hash' => $row->snapshot_hash,
                'transactions_count' => $row->transactions()->count(),
            ])
            ->all();
    }

    /**
     * Huella del acta: cuenta, fecha, saldos y líneas. Si algo se alterara
     * después, el hash dejaría de coincidir.
     *
     * @param  \Illuminate\Support\Collection<int, TreasuryTransaction>  $transactions
     * @param  \Illuminate\Support\Collection<int, TreasuryReconciliationItem>  $items
     */
    private function hash(
        TreasuryAccount $account,
        string $until,
        float $statementBalance,
        float $systemBalance,
        $transactions,
        $items,
    ): string {
        $payload = [
            'account' => $account->id,
            'until' => $until,
            'statement' => number_format($statementBalance, 2, '.', ''),
            'system' => number_format($systemBalance, 2, '.', ''),
            'transactions' => $transactions->sortBy('id')->map(fn ($tx) => [
                'id' => $tx->id,
                'amount' => number_format((float) $tx->amount, 2, '.', ''),
                'type' => $tx->type,
                'date' => $tx->transaction_date->toDateString(),
            ])->values()->all(),
            'items' => $items->sortBy('id')->map(fn ($item) => [
                'id' => $item->id,
                'amount' => number_format((float) $item->amount, 2, '.', ''),
                'type' => $item->type,
            ])->values()->all(),
        ];

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
