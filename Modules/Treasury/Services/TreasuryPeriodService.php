<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryPeriod;
use Modules\Treasury\Entities\TreasuryTransaction;
use Modules\Treasury\Exceptions\TreasuryOperationException;

/**
 * Cierre de periodos de tesorería.
 *
 * Un periodo cerrado bloquea altas, ediciones y anulaciones con fecha dentro
 * del mes. El cierre guarda una foto por cuenta y moneda; la reapertura exige
 * motivo y queda auditada. Nada se elimina.
 */
class TreasuryPeriodService
{
    public function __construct(
        private TreasuryAuditService $audit,
        private TreasuryLedgerService $ledger,
    ) {
    }

    /** Periodo que contiene la fecha (exista o no en base de datos). */
    public function periodFor(string $date): array
    {
        $value = Carbon::parse($date);

        return ['year' => (int) $value->year, 'month' => (int) $value->month];
    }

    public function find(int $year, int $month): ?TreasuryPeriod
    {
        return TreasuryPeriod::query()
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->first();
    }

    public function isLocked(string $date): bool
    {
        $period = $this->periodFor($date);

        return TreasuryPeriod::query()
            ->where('period_year', $period['year'])
            ->where('period_month', $period['month'])
            ->where('status', TreasuryPeriod::STATUS_CLOSED)
            ->exists();
    }

    /**
     * Se llama antes de escribir en el libro. Lanza si la fecha está cerrada.
     */
    public function ensureDateIsOpen(string $date): void
    {
        if ($this->isLocked($date)) {
            throw TreasuryOperationException::closedPeriod(Carbon::parse($date)->format('d/m/Y'));
        }
    }

    /**
     * Cierra el periodo. Idempotente: si ya está cerrado devuelve el registro
     * existente sin volver a calcular ni sobrescribir la foto.
     */
    public function close(int $year, int $month, ?int $userId = null, ?string $notes = null): TreasuryPeriod
    {
        $existing = $this->find($year, $month);

        if ($existing?->isClosed()) {
            return $existing;
        }

        return DB::transaction(function () use ($year, $month, $userId, $notes, $existing) {
            $period = $existing ?: TreasuryPeriod::create([
                'period_year' => $year,
                'period_month' => $month,
                'status' => TreasuryPeriod::STATUS_OPEN,
            ]);

            $period = TreasuryPeriod::query()->lockForUpdate()->findOrFail($period->id);

            if ($period->isClosed()) {
                return $period;
            }

            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            TreasuryAccount::query()
                ->orderBy('id')
                ->get()
                ->each(function (TreasuryAccount $account) use ($period, $start, $end) {
                    $transactions = TreasuryTransaction::query()
                        ->active()
                        ->where('treasury_account_id', $account->id)
                        ->whereDate('transaction_date', '>=', $start->toDateString())
                        ->whereDate('transaction_date', '<=', $end->toDateString());

                    $incomes = round((float) (clone $transactions)->where('type', TreasuryTransaction::TYPE_INCOME)->sum('amount'), 2);
                    $expenses = round((float) (clone $transactions)->where('type', TreasuryTransaction::TYPE_EXPENSE)->sum('amount'), 2);
                    $count = (int) (clone $transactions)->count();

                    $closing = $this->ledger->accountBalance($account, $end->toDateString());

                    $period->balances()->updateOrCreate(
                        ['treasury_account_id' => $account->id],
                        [
                            'currency' => strtoupper((string) ($account->currency_type_id ?: 'PEN')) ?: 'PEN',
                            'opening_balance' => round($closing - $incomes + $expenses, 2),
                            'incomes' => $incomes,
                            'expenses' => $expenses,
                            'closing_balance' => $closing,
                            'tx_count' => $count,
                        ]
                    );
                });

            $period->status = TreasuryPeriod::STATUS_CLOSED;
            $period->closed_at = now();
            $period->closed_by = $userId;
            $period->notes = $notes;
            $period->save();

            $this->audit->log('period', $period->id, 'closed', [], [
                'period' => $period->label(),
                'notes' => $notes,
            ]);

            return $period;
        });
    }

    /**
     * Reabre el periodo con motivo obligatorio. No desbloquea movimientos ya
     * conciliados: esos siguen siendo inmutables.
     */
    public function reopen(int $year, int $month, string $reason, ?int $userId = null): TreasuryPeriod
    {
        $period = $this->find($year, $month);

        if (! $period) {
            throw new TreasuryOperationException('El periodo indicado no existe.');
        }

        if (! $period->isClosed()) {
            return $period;
        }

        $period->status = TreasuryPeriod::STATUS_OPEN;
        $period->reopened_at = now();
        $period->reopened_by = $userId;
        $period->notes = trim(($period->notes ? $period->notes . "\n" : '') . 'Reapertura: ' . $reason);
        $period->save();

        $this->audit->log('period', $period->id, 'reopened', [
            'status' => TreasuryPeriod::STATUS_CLOSED,
        ], [
            'status' => TreasuryPeriod::STATUS_OPEN,
            'reason' => $reason,
        ]);

        return $period;
    }

    /**
     * Listado de los últimos periodos con su estado, incluidos los meses sin
     * cierre registrado (para poder cerrarlos desde la pantalla).
     *
     * @return array<int, array<string, mixed>>
     */
    public function recent(int $months = 18): array
    {
        $rows = [];
        $cursor = Carbon::now()->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $year = (int) $cursor->year;
            $month = (int) $cursor->month;
            $period = $this->find($year, $month);
            $hasMovements = TreasuryTransaction::query()
                ->active()
                ->whereDate('transaction_date', '>=', $cursor->copy()->startOfMonth()->toDateString())
                ->whereDate('transaction_date', '<=', $cursor->copy()->endOfMonth()->toDateString())
                ->exists();

            $rows[] = [
                'year' => $year,
                'month' => $month,
                'label' => $period?->label() ?? $this->monthLabel($month, $year),
                'status' => $period?->status ?? TreasuryPeriod::STATUS_OPEN,
                'closed_at' => $period?->closed_at?->format('d/m/Y H:i'),
                'closed_by' => $period?->closer?->name,
                'reopened_at' => $period?->reopened_at?->format('d/m/Y H:i'),
                'notes' => $period?->notes,
                'has_movements' => $hasMovements,
                'is_current' => $i === 0,
            ];

            $cursor->subMonth();
        }

        return $rows;
    }

    private function monthLabel(int $month, int $year): string
    {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return ($months[$month] ?? $month) . ' ' . $year;
    }
}
