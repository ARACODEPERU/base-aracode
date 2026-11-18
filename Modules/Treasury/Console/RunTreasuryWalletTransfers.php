<?php

namespace Modules\Treasury\Console;

use Illuminate\Console\Command;
use Modules\Treasury\Services\TreasuryWalletTransferService;

/**
 * Ejecuta las transferencias de billeteras que ya cumplieron su fecha hábil.
 * Se programa cada minuto; si no hay ninguna vencida, no hace nada.
 */
class RunTreasuryWalletTransfers extends Command
{
    protected $signature = 'treasury:run-wallet-transfers';

    protected $description = 'Ejecuta las transferencias programadas de billeteras digitales a su banco';

    public function handle(TreasuryWalletTransferService $service): int
    {
        $result = $service->runDue();

        $this->info("Transferencias de billeteras ejecutadas: {$result['processed']}; fallidas: {$result['failed']}");

        return self::SUCCESS;
    }
}
