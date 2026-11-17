<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Facades\DB;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryReconciliation;
use Modules\Treasury\Entities\TreasuryReconciliationItem;
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
            'amount' => round(abs($amount), 2),
            'type' => $type,
            'status' => TreasuryReconciliationItem::STATUS_PENDING,
            'created_by' => $userId,
        ]);

        $this->audit->log('reconciliation_item', $item->id, 'created', [], [
            'account' => $account->displayName(),
            'cutoff_date' => $cutoffDate,
            'description' => $description,
            'amount' => $item->amount,
            'type' => $type,
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
