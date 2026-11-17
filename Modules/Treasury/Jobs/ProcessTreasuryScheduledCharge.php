<?php

namespace Modules\Treasury\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Treasury\Entities\TreasuryAccount;
use Modules\Treasury\Entities\TreasuryScheduledRun;
use Modules\Treasury\Entities\TreasuryTransaction;

/**
 * Ejecuta en cola el cobro programado de una cuenta (por ejemplo, la comisión bancaria mensual).
 * Un registro por cuenta y periodo (AAAA-MM) evita cobrar dos veces el mismo mes.
 */
class ProcessTreasuryScheduledCharge implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $treasuryAccountId,
        public string $period,
    ) {
    }

    public function handle(): void
    {
        DB::transaction(function () {
            $account = TreasuryAccount::query()->lockForUpdate()->find($this->treasuryAccountId);

            // Cuenta inexistente, inactiva o sin cobro programado: no se hace nada
            if (! $account || ! $account->status || ! $account->schedule_enabled || (float) $account->schedule_amount <= 0) {
                return;
            }

            // Si el periodo ya se cobró, se omite (idempotencia)
            $alreadyRun = TreasuryScheduledRun::query()
                ->where('treasury_account_id', $account->id)
                ->where('period', $this->period)
                ->exists();
            if ($alreadyRun) {
                return;
            }

            $transaction = TreasuryTransaction::create([
                'treasury_account_id' => $account->id,
                'transaction_date' => now()->toDateString(),
                'type' => TreasuryTransaction::TYPE_EXPENSE,
                'origin_type' => 'treasury_schedule',
                'origin_id' => $account->id,
                'amount' => $account->schedule_amount,
                'description' => $account->schedule_concept ?: 'Cobro programado',
                'source' => TreasuryTransaction::SOURCE_AUTO,
            ]);

            TreasuryScheduledRun::create([
                'treasury_account_id' => $account->id,
                'period' => $this->period,
                'treasury_transaction_id' => $transaction->id,
                'executed_at' => now(),
            ]);

            Log::info('[Treasury] Cobro programado registrado', [
                'account_id' => $account->id,
                'period' => $this->period,
            ]);
        });
    }
}
