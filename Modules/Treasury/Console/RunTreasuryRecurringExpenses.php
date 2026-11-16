<?php

namespace Modules\Treasury\Console;

use Illuminate\Console\Command;
use Modules\Treasury\Services\TreasuryRecurringExpenseService;

/**
 * Encola en la cola `database` los gastos recurrentes que vencen hoy.
 * El pago real lo ejecuta `queue:work`; el comando solo despacha.
 */
class RunTreasuryRecurringExpenses extends Command
{
    protected $signature = 'treasury:run-recurring';

    protected $description = 'Encola los gastos recurrentes de Tesorería que vencen hoy';

    public function handle(TreasuryRecurringExpenseService $recurring): int
    {
        $count = $recurring->dispatchDue();

        $this->info("Gastos recurrentes encolados: {$count}");

        return self::SUCCESS;
    }
}
