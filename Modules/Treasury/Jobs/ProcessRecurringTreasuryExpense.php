<?php

namespace Modules\Treasury\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Treasury\Entities\TreasuryRecurringExpense;
use Modules\Treasury\Services\TreasuryRecurringExpenseService;

/**
 * Ejecuta un gasto recurrente en la cola `database` (queue:work).
 *
 * Es seguro reintentarlo: la ejecución está protegida por la clave única
 * (recurrente, periodo) y la orden por su `idempotency_key`.
 */
class ProcessRecurringTreasuryExpense implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public int $recurringId)
    {
    }

    public function handle(TreasuryRecurringExpenseService $service): void
    {
        $recurring = TreasuryRecurringExpense::query()->find($this->recurringId);

        if (! $recurring || ! $recurring->status) {
            return;
        }

        $service->run($recurring);
    }
}
