<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryForecastAdjustment;
use Modules\Treasury\Entities\TreasuryPaymentOrder;
use Modules\Treasury\Entities\TreasuryRecurringExpense;

/**
 * Previsión de flujo de caja por moneda.
 *
 * Entradas: cuotas y cronogramas pendientes de cobro (CxC de Ventas) más los
 * ajustes manuales. Salidas: órdenes de pago aprobadas o programadas, gastos
 * recurrentes futuros, cargos programados de cuentas y ajustes manuales.
 *
 * Es una estimación: se etiqueta como tal y no sustituye al saldo real del
 * libro. Todo lo que no declara moneda se interpreta en soles.
 */
class TreasuryForecastService
{
    public function __construct(
        private TreasuryLedgerService $ledger,
        private TreasuryCurrencyService $currency,
        private TreasuryTaxService $taxes,
    ) {
    }

    /**
     * Monedas disponibles según las cuentas activas.
     *
     * @return array<int, string>
     */
    public function currencies(): array
    {
        $list = TreasuryAccount::query()
            ->active()
            ->pluck('currency_type_id')
            ->map(fn ($value) => strtoupper(trim((string) $value)) ?: 'PEN')
            ->unique()
            ->values()
            ->all();

        return $list !== [] ? $list : ['PEN'];
    }

    /**
     * @return array<string, mixed>
     */
    public function build(string $currency = 'PEN', int $days = 30, ?float $collectionRatio = null): array
    {
        $currency = $this->currency->normalize($currency);
        $days = max(7, min(180, $days));
        $ratio = $collectionRatio ?? (float) config('treasury.forecast_collection_ratio', 1.0);
        $ratio = max(0, min(1, $ratio));

        $from = Carbon::today();
        $to = $from->copy()->addDays($days);

        // La detracción es dinero restringido: no entra al saldo disponible.
        $accounts = TreasuryAccount::query()->active()
            ->where('type', '<>', TreasuryAccount::TYPE_DETRACTION)
            ->get()
            ->filter(fn (TreasuryAccount $account) => $this->currency->accountCurrency($account) === $currency);

        $opening = round($accounts->sum(fn (TreasuryAccount $account) => $this->ledger->displayBalance($account)), 2);

        $buckets = [];

        for ($cursor = $from->copy(); $cursor->lte($to); $cursor->addDay()) {
            $buckets[$cursor->toDateString()] = [
                'date' => $cursor->toDateString(),
                'label' => $cursor->format('d/m'),
                'inflows' => 0.0,
                'outflows' => 0.0,
                'net' => 0.0,
                'balance' => 0.0,
                'items' => [],
            ];
        }

        $this->addReceivables($buckets, $currency, $from, $to, $ratio);
        $this->addOpenOrders($buckets, $currency, $from, $to);
        $this->addRecurring($buckets, $currency, $from, $to);
        $this->addScheduledCharges($buckets, $accounts, $from, $to);
        $this->addTaxes($buckets, $currency, $from, $to);
        $this->addAdjustments($buckets, $currency, $from, $to);

        $balance = $opening;
        $minBalance = $opening;
        $minDate = $from->toDateString();
        $rows = [];

        foreach ($buckets as $date => $bucket) {
            $net = round($bucket['inflows'] - $bucket['outflows'], 2);
            $balance = round($balance + $net, 2);

            if ($balance < $minBalance) {
                $minBalance = $balance;
                $minDate = $date;
            }

            $rows[] = [
                'date' => $date,
                'label' => $bucket['label'],
                'inflows' => round($bucket['inflows'], 2),
                'outflows' => round($bucket['outflows'], 2),
                'net' => $net,
                'balance' => $balance,
                'items' => $bucket['items'],
            ];
        }

        $minimum = (float) config('treasury.forecast_min_balance', 0);
        $warnings = [];

        if ($minBalance < $minimum) {
            $warnings[] = 'El saldo proyectado cae por debajo del mínimo configurado el ' . Carbon::parse($minDate)->format('d/m/Y') . '.';
        }

        if ($currency !== 'PEN') {
            $rate = $this->currency->rate($currency);

            if (! $rate) {
                $warnings[] = 'No hay tipo de cambio para ' . $currency . ': la previsión está solo en su moneda.';
            } elseif ($rate['is_stale']) {
                $warnings[] = 'El tipo de cambio de ' . $currency . ' es del ' . Carbon::parse((string) $rate['date'])->format('d/m/Y') . '.';
            }
        }

        $totals = [
            'inflows' => round(array_sum(array_column($rows, 'inflows')), 2),
            'outflows' => round(array_sum(array_column($rows, 'outflows')), 2),
            'net' => round(array_sum(array_column($rows, 'net')), 2),
            'closing' => $balance,
        ];

        return [
            'currency' => $currency,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'days' => $days,
            'collection_ratio' => $ratio,
            'opening_balance' => $opening,
            'accounts_count' => $accounts->count(),
            'rows' => $rows,
            'totals' => $totals,
            'min_balance' => round($minBalance, 2),
            'min_date' => $minDate,
            'warnings' => $warnings,
            'estimated' => true,
        ];
    }

    /**
     * Cuotas de comprobantes y cronogramas pendientes de cobro.
     *
     * @param  array<string, array<string, mixed>>  $buckets
     */
    private function addReceivables(array &$buckets, string $currency, Carbon $from, Carbon $to, float $ratio): void
    {
        $quotaDocumentIds = [];

        if (Schema::hasTable('sale_document_quotas') && Schema::hasTable('sale_documents')) {
            $quotas = DB::table('sale_document_quotas as q')
                ->join('sale_documents as d', 'd.id', '=', 'q.sale_document_id')
                ->whereRaw('COALESCE(q.balance, q.amount) > 0')
                ->whereBetween('q.due_date', [$from->toDateString(), $to->toDateString()])
                ->whereNotIn('d.invoice_status', ['Anulada', 'Por anular', 'Rechazada'])
                ->select([
                    'q.id', 'q.due_date', 'q.amount', 'q.balance', 'q.quota_number',
                    'd.invoice_type_currency as currency', 'd.invoice_serie', 'd.invoice_correlative',
                    'd.id as document_id',
                ])
                ->get();

            foreach ($quotas as $quota) {
                $quotaDocumentIds[] = (int) $quota->document_id;

                if ($this->currency->normalize($quota->currency) !== $currency) {
                    continue;
                }

                $amount = round((float) ($quota->balance ?? 0) ?: (float) $quota->amount, 2);

                $this->push($buckets, (string) $quota->due_date, 'in', round($amount * $ratio, 2), [
                    'concept' => 'Cuota ' . ($quota->quota_number ?: '') . ' ' . trim((string) $quota->invoice_serie . '-' . (string) $quota->invoice_correlative),
                    'type' => 'cuota',
                    'amount' => round($amount * $ratio, 2),
                ]);
            }
        }

        if (! Schema::hasTable('sale_payment_schedules')) {
            return;
        }

        $schedules = DB::table('sale_payment_schedules as s')
            ->leftJoin('sale_documents as d', 'd.id', '=', 's.document_id')
            ->whereBetween('s.payment_date', [$from->toDateString(), $to->toDateString()])
            ->where(function ($query) {
                $query->where('s.is_paid', false)->orWhereNull('s.is_paid');
            })
            ->whereRaw('COALESCE(s.remaining_amount, s.amount_to_pay) > 0')
            ->select([
                's.id', 's.payment_date', 's.amount_to_pay', 's.remaining_amount',
                's.installment_number', 's.document_id',
                'd.invoice_type_currency as currency', 'd.invoice_serie', 'd.invoice_correlative',
            ])
            ->get();

        foreach ($schedules as $schedule) {
            // Si el comprobante ya se proyecta por cuotas, no se cuenta dos veces.
            if ($schedule->document_id && in_array((int) $schedule->document_id, $quotaDocumentIds, true)) {
                continue;
            }

            if ($this->currency->normalize($schedule->currency) !== $currency) {
                continue;
            }

            $amount = round((float) ($schedule->remaining_amount ?? 0) ?: (float) $schedule->amount_to_pay, 2);

            $this->push($buckets, (string) $schedule->payment_date, 'in', round($amount * $ratio, 2), [
                'concept' => 'Cronograma cuota ' . ($schedule->installment_number ?: ''),
                'type' => 'cronograma',
                'amount' => round($amount * $ratio, 2),
            ]);
        }
    }

    /**
     * Órdenes de pago aprobadas o programadas, aún no pagadas.
     *
     * @param  array<string, array<string, mixed>>  $buckets
     */
    private function addOpenOrders(array &$buckets, string $currency, Carbon $from, Carbon $to): void
    {
        $orders = TreasuryPaymentOrder::query()
            ->open()
            ->where('currency', $currency)
            ->orderBy('id')
            ->get();

        foreach ($orders as $order) {
            $date = $order->forecastDate();

            if (! $date || $date < $from->toDateString() || $date > $to->toDateString()) {
                continue;
            }

            $this->push($buckets, $date, 'out', (float) $order->amount, [
                'concept' => $order->code . ' · ' . $order->description,
                'type' => 'orden',
                'amount' => (float) $order->amount,
            ]);
        }
    }

    /**
     * Ocurrencias futuras de gastos recurrentes.
     *
     * @param  array<string, array<string, mixed>>  $buckets
     */
    private function addRecurring(array &$buckets, string $currency, Carbon $from, Carbon $to): void
    {
        $recurring = TreasuryRecurringExpense::query()
            ->active()
            ->where('currency', $currency)
            ->get();

        foreach ($recurring as $expense) {
            foreach ($expense->occurrencesBetween($from->toDateString(), $to->toDateString()) as $date) {
                $this->push($buckets, $date, 'out', (float) $expense->amount, [
                    'concept' => 'Recurrente · ' . $expense->concept,
                    'type' => 'recurrente',
                    'amount' => (float) $expense->amount,
                ]);
            }
        }
    }

    /**
     * Cargos programados de las cuentas (por ejemplo, mantenimiento mensual).
     *
     * @param  array<string, array<string, mixed>>  $buckets
     * @param  \Illuminate\Support\Collection<int, TreasuryAccount>  $accounts
     */
    private function addScheduledCharges(array &$buckets, $accounts, Carbon $from, Carbon $to): void
    {
        foreach ($accounts as $account) {
            if (! $account->schedule_enabled || (float) $account->schedule_amount <= 0) {
                continue;
            }

            $day = (int) ($account->schedule_day ?: 1);

            for ($cursor = $from->copy()->startOfMonth(); $cursor->lte($to); $cursor->addMonth()) {
                $chargeDate = $cursor->copy()->day(min($day, $cursor->daysInMonth));

                if ($chargeDate->lt($from) || $chargeDate->gt($to)) {
                    continue;
                }

                $this->push($buckets, $chargeDate->toDateString(), 'out', (float) $account->schedule_amount, [
                    'concept' => 'Cargo programado · ' . $account->displayName(),
                    'type' => 'cargo_programado',
                    'amount' => (float) $account->schedule_amount,
                ]);
            }
        }
    }

    /**
     * IGV por pagar: el impuesto de las boletas y facturas emitidas vence el
     * mes siguiente. Solo se proyecta lo que aún no tiene pago registrado.
     *
     * @param  array<string, array<string, mixed>>  $buckets
     */
    private function addTaxes(array &$buckets, string $currency, Carbon $from, Carbon $to): void
    {
        if (! (bool) config('treasury.tax_igv_enabled', true)) {
            return;
        }

        // Se recorre desde el mes anterior: su IGV vence dentro del horizonte.
        $cursor = $from->copy()->startOfMonth()->subMonthNoOverflow();
        $limit = $to->copy()->startOfMonth();

        while ($cursor->lte($limit)) {
            $period = $cursor->format('Y-m');
            $due = $this->taxes->dueDate($period);

            if ($due >= $from->toDateString() && $due <= $to->toDateString()) {
                $amount = $this->taxes->unpaidForPeriod($period, $currency);

                $this->push($buckets, $due, 'out', $amount, [
                    'concept' => 'IGV por pagar ' . $period,
                    'type' => 'igv',
                    'amount' => $amount,
                ]);
            }

            $cursor->addMonth();
        }
    }

    /**
     * Ajustes manuales de la previsión.
     *
     * @param  array<string, array<string, mixed>>  $buckets
     */
    private function addAdjustments(array &$buckets, string $currency, Carbon $from, Carbon $to): void
    {
        $adjustments = TreasuryForecastAdjustment::query()
            ->where('currency', $currency)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get();

        foreach ($adjustments as $adjustment) {
            $this->push(
                $buckets,
                $adjustment->date->toDateString(),
                $adjustment->type === TreasuryForecastAdjustment::TYPE_INCOME ? 'in' : 'out',
                (float) $adjustment->amount,
                [
                    'concept' => $adjustment->concept,
                    'type' => 'ajuste',
                    'amount' => (float) $adjustment->amount,
                ]
            );
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $buckets
     * @param  array<string, mixed>  $item
     */
    private function push(array &$buckets, string $date, string $direction, float $amount, array $item): void
    {
        if ($amount <= 0 || ! isset($buckets[$date])) {
            return;
        }

        if ($direction === 'in') {
            $buckets[$date]['inflows'] += $amount;
        } else {
            $buckets[$date]['outflows'] += $amount;
        }

        $buckets[$date]['items'][] = $item;
    }
}
