<?php

namespace Modules\Sales\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\Sales\Services\Facturador3\Facturador3ConnectionService;
use Modules\Sales\Services\Facturador3\Facturador3ImportService;

class VerifyFacturador3ImportCommand extends Command
{
    protected $signature = 'sales:import-facturador3-verify
        {--output= : Ruta CSV de discrepancias (storage/app/)}';

    protected $description = 'Compara stock actual facturador3 vs kardex base-aracode por local.';

    public function handle(
        Facturador3ConnectionService $connection,
        Facturador3ImportService $importService,
    ): int {
        try {
            $connection->assertConfigured();
            $establishmentMap = $importService->resolveEstablishmentMap();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Verificando stock...');
        $discrepancies = $importService->verifyStock($establishmentMap);

        $this->info('Discrepancias encontradas: '.count($discrepancies));

        if (empty($discrepancies)) {
            return self::SUCCESS;
        }

        $output = $this->option('output') ?: 'exports/facturador3_verify_'.date('Ymd_His').'.csv';
        $lines = ['f3_item_id,product_id,interne,local_id,f3_stock,ba_stock,diff'];

        foreach ($discrepancies as $row) {
            $lines[] = implode(',', [
                $row['f3_item_id'],
                $row['product_id'],
                '"'.str_replace('"', '""', $row['interne']).'"',
                $row['local_id'],
                $row['f3_stock'],
                $row['ba_stock'],
                $row['diff'],
            ]);
        }

        Storage::disk('local')->put($output, implode("\n", $lines));
        $this->warn('Reporte guardado en: storage/app/'.$output);

        foreach (array_slice($discrepancies, 0, 10) as $row) {
            $this->line("  {$row['interne']} local {$row['local_id']}: F3={$row['f3_stock']} BA={$row['ba_stock']}");
        }

        if (count($discrepancies) > 10) {
            $this->line('  ... y '.(count($discrepancies) - 10).' más.');
        }

        return self::FAILURE;
    }
}
