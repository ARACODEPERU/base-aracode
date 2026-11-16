<?php

namespace Modules\Treasury\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Entities\TreasuryPaymentOrder;
use Modules\Treasury\Entities\TreasuryRecurringExpense;
use Modules\Treasury\Entities\TreasuryRecurringRun;
use Modules\Treasury\Exceptions\TreasuryOperationException;
use Modules\Treasury\Jobs\ProcessRecurringTreasuryExpense;

/**
 * Gastos recurrentes.
 *
 * Cada ocurrencia tiene clave (recurrente, periodo) en `treasury_recurring_runs`,
 * así que el job puede reintentarse o duplicarse sin pagar dos veces el mismo
 * mes. El comando programado solo encola; el trabajo real lo hace la cola
 * `database` con `queue:work`.
 */
class TreasuryRecurringExpenseService
{
    public function __construct(
        private TreasuryPaymentOrderService $orders,
        private TreasuryAuditService $audit,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?int $userId = null): TreasuryRecurringExpense
    {
        $amount = round((float) ($data['amount'] ?? 0), 2);

        if ($amount <= 0) {
            throw new TreasuryOperationException('El monto del gasto recurrente debe ser mayor que cero.');
        }

        $start = Carbon::parse($data['start_date'] ?? now()->toDateString());
        $next = $this->firstRunDate($data, $start);

        $recurring = TreasuryRecurringExpense::create([
            'concept' => $data['concept'],
            'type' => $data['type'] ?? 'service',
            'supplier_id' => $data['supplier_id'] ?? null,
            'treasury_category_id' => $data['treasury_category_id'] ?? null,
            'currency' => app(TreasuryCurrencyService::class)->normalize($data['currency'] ?? null),
            'amount' => $amount,
            'treasury_account_id' => $data['treasury_account_id'] ?? null,
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'frequency' => $data['frequency'] ?? TreasuryRecurringExpense::FREQ_MONTHLY,
            'day_of_month' => $data['day_of_month'] ?? null,
            'day_of_week' => $data['day_of_week'] ?? null,
            'start_date' => $start->toDateString(),
            'end_date' => $data['end_date'] ?? null,
            'next_run_date' => $next->toDateString(),
            'auto_pay' => (bool) ($data['auto_pay'] ?? false),
            'status' => (bool) ($data['status'] ?? true),
            'notes' => $data['notes'] ?? null,
            'created_by' => $userId,
        ]);

        $this->audit->log('recurring_expense', $recurring->id, 'created', [], [
            'concept' => $recurring->concept,
            'amount' => $recurring->amount,
            'frequency' => $recurring->frequency,
            'next_run_date' => $recurring->next_run_date->toDateString(),
            'auto_pay' => $recurring->auto_pay,
        ]);

        return $recurring;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TreasuryRecurringExpense $recurring, array $data, ?int $userId = null): TreasuryRecurringExpense
    {
        $recurring->fill([
            'concept' => $data['concept'] ?? $recurring->concept,
            'type' => $data['type'] ?? $recurring->type,
            'supplier_id' => $data['supplier_id'] ?? $recurring->supplier_id,
            'treasury_category_id' => $data['treasury_category_id'] ?? $recurring->treasury_category_id,
            'amount' => round((float) ($data['amount'] ?? $recurring->amount), 2),
            'treasury_account_id' => $data['treasury_account_id'] ?? $recurring->treasury_account_id,
            'payment_method_id' => $data['payment_method_id'] ?? $recurring->payment_method_id,
            'frequency' => $data['frequency'] ?? $recurring->frequency,
            'day_of_month' => $data['day_of_month'] ?? $recurring->day_of_month,
            'day_of_week' => $data['day_of_week'] ?? $recurring->day_of_week,
            'end_date' => $data['end_date'] ?? $recurring->end_date,
            'auto_pay' => array_key_exists('auto_pay', $data) ? (bool) $data['auto_pay'] : $recurring->auto_pay,
            'status' => array_key_exists('status', $data) ? (bool) $data['status'] : $recurring->status,
            'notes' => $data['notes'] ?? $recurring->notes,
        ]);

        $recurring->save();

        $this->audit->log('recurring_expense', $recurring->id, 'updated', [], $recurring->only([
            'concept', 'amount', 'frequency', 'auto_pay', 'status', 'next_run_date',
        ]));

        return $recurring;
    }

    /**
     * Ejecuta la ocurrencia pendiente del recurrente. Devuelve la orden creada
     * o la existente si el periodo ya se procesó.
     */
    public function run(TreasuryRecurringExpense $recurring, ?int $userId = null): ?TreasuryPaymentOrder
    {
        $runDate = $recurring->next_run_date?->copy() ?? Carbon::today();

        if ($recurring->end_date && $runDate->gt($recurring->end_date)) {
            $recurring->status = false;
            $recurring->save();

            return null;
        }

        $period = $runDate->format('Y-m');

        $existingRun = TreasuryRecurringRun::query()
            ->where('treasury_recurring_expense_id', $recurring->id)
            ->where('period', $period)
            ->first();

        if ($existingRun) {
            return $existingRun->order;
        }

        try {
            $order = $this->orders->create([
                'idempotency_key' => 'recurring:' . $recurring->id . ':' . $period,
                'type' => TreasuryPaymentOrder::TYPE_RECURRING,
                'supplier_id' => $recurring->supplier_id,
                'description' => $recurring->concept,
                'treasury_category_id' => $recurring->treasury_category_id,
                'currency' => $recurring->currency,
                'amount' => (float) $recurring->amount,
                'issue_date' => $runDate->toDateString(),
                'due_date' => $runDate->toDateString(),
                'scheduled_date' => $runDate->toDateString(),
                'treasury_account_id' => $recurring->treasury_account_id,
                'payment_method_id' => $recurring->payment_method_id,
                'recurring_id' => $recurring->id,
                'notes' => 'Generado automáticamente por gasto recurrente.',
            ], $userId);

            $error = null;

            if ($recurring->auto_pay) {
                try {
                    $this->orders->pay($order, $userId, $runDate->toDateString());
                } catch (\Throwable $e) {
                    // La orden queda aprobada para pago manual; el error se
                    // guarda en la ejecución y se ve en la pantalla.
                    $error = $e->getMessage();
                }
            }

            TreasuryRecurringRun::create([
                'treasury_recurring_expense_id' => $recurring->id,
                'period' => $period,
                'treasury_payment_order_id' => $order->id,
                'executed_at' => now(),
                'error' => $error,
            ]);

            $recurring->last_run_at = now();
            $recurring->next_run_date = $recurring->advanceFrom($runDate)->toDateString();
            $recurring->save();

            $this->audit->log('recurring_expense', $recurring->id, 'executed', [], [
                'period' => $period,
                'payment_order' => $order->code,
                'error' => $error,
            ]);

            return $order;
        } catch (\Throwable $e) {
            Log::error('[Treasury] Falló el gasto recurrente', [
                'recurring_id' => $recurring->id,
                'period' => $period,
                'error' => $e->getMessage(),
            ]);

            // La fila de ejecución guarda el error y evita reintentos infinitos.
            TreasuryRecurringRun::updateOrCreate(
                [
                    'treasury_recurring_expense_id' => $recurring->id,
                    'period' => $period,
                ],
                [
                    'executed_at' => now(),
                    'error' => mb_substr($e->getMessage(), 0, 900),
                ]
            );

            $recurring->last_run_at = now();
            $recurring->next_run_date = $recurring->advanceFrom($runDate)->toDateString();
            $recurring->save();

            return null;
        }
    }

    /**
     * Encola los recurrentes vencidos. Devuelve cuántos jobs se encolaron.
     */
    public function dispatchDue(?string $date = null): int
    {
        $due = TreasuryRecurringExpense::query()
            ->due($date)
            ->orderBy('next_run_date')
            ->limit(100)
            ->get();

        foreach ($due as $recurring) {
            ProcessRecurringTreasuryExpense::dispatch($recurring->id);
        }

        return $due->count();
    }

    /**
     * Primera fecha de ejecución según la configuración.
     *
     * @param  array<string, mixed>  $data
     */
    private function firstRunDate(array $data, Carbon $start): Carbon
    {
        $frequency = $data['frequency'] ?? TreasuryRecurringExpense::FREQ_MONTHLY;

        if ($frequency === TreasuryRecurringExpense::FREQ_MONTHLY && ! empty($data['day_of_month'])) {
            $day = (int) $data['day_of_month'];
            $candidate = $start->copy()->day(min($day, $start->daysInMonth));

            if ($candidate->lt($start)) {
                $nextMonth = $start->copy()->startOfMonth()->addMonth();
                $candidate = $nextMonth->day(min($day, $nextMonth->daysInMonth));
            }

            return $candidate;
        }

        return $start->copy();
    }
}
