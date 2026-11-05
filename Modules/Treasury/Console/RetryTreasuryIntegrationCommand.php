<?php

namespace Modules\Treasury\Console;

use Illuminate\Console\Command;
use Modules\Treasury\Entities\TreasurySourceMovement;
use Modules\Treasury\Services\TreasuryIntegrationService;

class RetryTreasuryIntegrationCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'treasury:integration-retry
                            {--id= : Reintenta solo el movimiento con este id}
                            {--limit=200 : Máximo de movimientos a procesar}
                            {--dry-run : Lista los movimientos pendientes o fallidos sin reprocesarlos}';

    /**
     * The console command description.
     */
    protected $description = 'Reintenta los movimientos de integración pendientes o fallidos (idempotente)';

    public function handle(TreasuryIntegrationService $service): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $id = $this->option('id') ? (int) $this->option('id') : null;

        if ($this->option('dry-run')) {
            $pending = TreasurySourceMovement::query()
                ->whereIn('status', [
                    TreasurySourceMovement::STATUS_FAILED,
                    TreasurySourceMovement::STATUS_PENDING,
                ])
                ->when($id, fn ($query) => $query->whereKey($id))
                ->orderBy('id')
                ->limit($limit)
                ->get();

            if ($pending->isEmpty()) {
                $this->info('No hay movimientos pendientes ni fallidos.');

                return self::SUCCESS;
            }

            foreach ($pending as $movement) {
                $this->line(sprintf(
                    '  #%d %s %s#%d · %s · %s %s · %s',
                    $movement->id,
                    $movement->status,
                    $movement->source_type,
                    $movement->source_id,
                    $movement->operation,
                    number_format((float) $movement->amount, 2),
                    $movement->currency,
                    $movement->error_message ?: 'sin detalle'
                ));
            }

            $this->info("Total listado: {$pending->count()} (dry-run, nada reprocesado)");

            return self::SUCCESS;
        }

        $result = $service->retryPending($limit, $id);

        $this->newLine();
        $this->info("Procesados: {$result['processed']} · Ignorados: {$result['ignored']} · Siguen fallidos: {$result['failed']}");

        if ($result['failed'] > 0) {
            $this->warn('Revisa error_message en treasury_source_movements para el detalle de los fallos.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
