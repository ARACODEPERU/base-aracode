<?php

namespace Modules\Treasury\Console;

use Illuminate\Console\Command;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Jobs\ProcessTreasuryScheduledCharge;

/**
 * Revisa cada minuto las cuentas con cobro programado y encola el de las que
 * corresponden a este día y hora. El día "31" en meses cortos usa el último día.
 */
class RunTreasuryScheduledCharges extends Command
{
    protected $signature = 'treasury:run-scheduled';

    protected $description = 'Encola los cobros programados de Tesorería que corresponden a este día y hora';

    public function handle(): int
    {
        $now = now();
        $lastDayOfMonth = (int) $now->daysInMonth;
        $currentTime = $now->format('H:i');
        $period = $now->format('Y-m');

        $dispatched = 0;

        TreasuryAccount::query()
            ->where('status', true)
            ->where('schedule_enabled', true)
            ->where('schedule_time', 'like', $currentTime . '%')
            ->get()
            ->each(function (TreasuryAccount $account) use ($now, $lastDayOfMonth, $period, &$dispatched) {
                // Día programado ajustado al último día del mes cuando no existe
                $scheduledDay = min((int) $account->schedule_day, $lastDayOfMonth);

                if ((int) $now->day !== $scheduledDay) {
                    return;
                }

                ProcessTreasuryScheduledCharge::dispatch($account->id, $period);
                $dispatched++;
            });

        $this->info("Cobros programados encolados: {$dispatched}");

        return self::SUCCESS;
    }
}
