<?php

namespace Modules\Sales\Console;

use Illuminate\Console\Command;
use Modules\Sales\Services\Facturador3\Facturador3ConnectionService;
use Modules\Sales\Services\Facturador3\Facturador3ImportService;

class ImportFacturador3Command extends Command
{
    protected $signature = 'sales:import-facturador3
        {--tenant-db= : Nombre de la BD tenant Facturador3}
        {--phase=all : setup|products|stock|all}
        {--user-id=1 : ID del usuario que inicia el import}
        {--chunk= : Tamaño de lote (override)}';

    protected $description = 'Importa productos y stock actual desde facturador3 (sin historial de kardex).';

    public function handle(
        Facturador3ConnectionService $connection,
        Facturador3ImportService $importService,
    ): int {
        $phase = $this->option('phase');
        $tenantDb = $this->option('tenant-db');

        if ($tenantDb) {
            $connection->configureTenantDatabase($tenantDb);
        }

        try {
            $connection->assertConfigured();
            $connection->testConnection();

            $missing = $connection->requiredTablesExist();
            if (! empty($missing)) {
                $this->error('Tablas faltantes en facturador3: '.implode(', ', $missing));

                return self::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $userId = (int) $this->option('user-id');
        $chunkSize = $this->option('chunk') ? (int) $this->option('chunk') : null;

        if ($phase === 'setup') {
            return $this->runSetup($connection, $importService);
        }

        if ($phase === 'all') {
            if (! $tenantDb) {
                $this->error('Indique --tenant-db=tenant_xxx para importación completa.');

                return self::FAILURE;
            }

            $job = $importService->process($userId, [
                'tenant_database' => $tenantDb,
                'chunk_size' => $chunkSize,
            ]);

            $this->info("Job #{$job->id} — productos + stock en cola (auto-encadenado).");
            $this->line('Worker: php artisan queue:work --queue='.config('facturador3_import.queue', 'imports'));

            return self::SUCCESS;
        }

        $establishmentMap = $importService->resolveEstablishmentMap();
        $meta = [
            'tenant_database' => $tenantDb,
            'establishment_map' => $establishmentMap,
            'import_with_stock' => true,
            'import_without_stock' => true,
            'auto_stock' => false,
            'skip_zero_stock' => true,
        ];

        $job = $importService->createJob($userId, $phase, $meta);

        if ($chunkSize) {
            $job->update(['chunk_size' => $chunkSize]);
        }

        if ($phase === 'products') {
            $this->info('Despachando importación de productos...');
            $importService->dispatchProductsImport($job);
            $this->info("Job #{$job->id} — fase productos en cola.");
        }

        if ($phase === 'stock') {
            $this->info('Despachando importación de stock...');
            $importService->dispatchStockImport($job);
            $this->info("Job #{$job->id} — fase stock en cola.");
        }

        $this->line('Worker: php artisan queue:work --queue='.config('facturador3_import.queue', 'imports'));

        return self::SUCCESS;
    }

    protected function runSetup(
        Facturador3ConnectionService $connection,
        Facturador3ImportService $importService,
    ): int {
        $this->info('Conexión OK. Establecimientos facturador3:');
        foreach ($connection->getEstablishments() as $est) {
            $this->line("  [{$est['id']}] {$est['description']} (código: {$est['code']})");
        }

        $this->newLine();
        $this->info('Locales base-aracode:');
        foreach (\App\Models\LocalSale::orderBy('id')->get(['id', 'description']) as $local) {
            $this->line("  [{$local->id}] {$local->description}");
        }

        $this->newLine();
        $maps = $importService->syncCatalogMaps();
        $this->info('Categorías sincronizadas: '.count($maps['categories']));
        $this->info('Marcas sincronizadas: '.count($maps['brands']));
        $this->info('Items en F3: '.$connection->countItems());
        $this->info('Filas item_warehouse (stock): '.$connection->countStockRows());

        return self::SUCCESS;
    }
}
