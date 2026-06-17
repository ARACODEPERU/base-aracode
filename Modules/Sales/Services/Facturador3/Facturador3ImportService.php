<?php

namespace Modules\Sales\Services\Facturador3;

use App\Models\Kardex;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Entities\Facturador3ImportJob;
use Modules\Sales\Entities\Facturador3ImportMap;
use Modules\Sales\Entities\SaleProductBrand;
use Modules\Sales\Entities\SaleProductCategory;
use Modules\Sales\Jobs\ImportFacturador3ProductsChunk;
use Modules\Sales\Jobs\ImportFacturador3StockChunk;
use RuntimeException;

class Facturador3ImportService
{
    public function __construct(
        protected Facturador3ConnectionService $connection,
        protected Facturador3ProductMapper $mapper,
        protected Facturador3PreviewService $preview,
    ) {}

    public function resolveEstablishmentMap(?array $override = null): array
    {
        if ($override !== null && ! empty($override)) {
            return array_map('intval', $override);
        }

        $filePath = storage_path('app/facturador3_establishment_map.json');
        if (file_exists($filePath)) {
            $decoded = json_decode(file_get_contents($filePath), true);
            if (is_array($decoded) && ! empty($decoded)) {
                return array_map('intval', $decoded);
            }
        }

        throw new RuntimeException('Debe configurar el mapeo de establecimientos antes de importar.');
    }

    public function process(int $userId, array $options): Facturador3ImportJob
    {
        $tenantDatabase = trim((string) ($options['tenant_database'] ?? ''));
        $this->connection->configureTenantDatabase($tenantDatabase);
        $this->connection->testConnection();

        $missing = $this->connection->requiredTablesExist();
        if (! empty($missing)) {
            throw new RuntimeException('Tablas faltantes en Facturador3: '.implode(', ', $missing));
        }

        $establishmentMap = $this->resolveEstablishmentMap($options['establishment_map'] ?? null);

        if (! empty($options['excluded_item_ids'])) {
            $this->preview->syncExclusions($tenantDatabase, $userId, $options['excluded_item_ids']);
        }

        $excludedIds = $this->preview->resolveExcludedIds($tenantDatabase);
        $importWithStock = (bool) ($options['import_with_stock'] ?? true);
        $importWithoutStock = (bool) ($options['import_without_stock'] ?? true);

        if (! $importWithStock && ! $importWithoutStock) {
            throw new RuntimeException('Debe seleccionar al menos un tipo de producto a importar.');
        }

        $meta = [
            'tenant_database' => $tenantDatabase,
            'establishment_map' => $establishmentMap,
            'import_with_stock' => $importWithStock,
            'import_without_stock' => $importWithoutStock,
            'min_stock' => $options['min_stock'] ?? null,
            'excluded_item_ids' => $excludedIds,
            'auto_stock' => true,
            'skip_zero_stock' => true,
        ];

        $job = $this->createJob($userId, 'products', $meta);

        if (! empty($options['chunk_size'])) {
            $job->update(['chunk_size' => (int) $options['chunk_size']]);
        }

        $this->dispatchProductsImport($job);

        return $job->fresh();
    }

    public function syncCatalogMaps(): array
    {
        $categoryMap = [];
        $brandMap = [];

        $categories = $this->connection->connection()->table('categories')->get(['id', 'name']);
        foreach ($categories as $category) {
            $name = trim((string) $category->name);
            if ($name === '') {
                continue;
            }

            $existing = SaleProductCategory::where('description', $name)->first();
            $categoryMap[$category->id] = $existing
                ? $existing->id
                : SaleProductCategory::create(['description' => $name, 'status' => true])->id;
        }

        $brands = $this->connection->connection()->table('brands')->get(['id', 'name']);
        foreach ($brands as $brand) {
            $name = trim((string) $brand->name);
            if ($name === '') {
                continue;
            }

            $existing = SaleProductBrand::where('description', $name)->first();
            $brandMap[$brand->id] = $existing
                ? $existing->id
                : SaleProductBrand::create(['description' => $name])->id;
        }

        return [
            'categories' => $categoryMap,
            'brands' => $brandMap,
        ];
    }

    public function createJob(int $userId, string $phase, array $meta = []): Facturador3ImportJob
    {
        return Facturador3ImportJob::create([
            'user_id' => $userId,
            'phase' => $phase,
            'status' => 'pending',
            'progress' => 0,
            'chunk_size' => (int) config('facturador3_import.chunk_size', 1000),
            'meta' => $meta,
        ]);
    }

    public function dispatchProductsImport(Facturador3ImportJob $job): void
    {
        $this->connection->configureFromJobMeta($job->meta);
        $establishmentMap = $this->resolveEstablishmentMap($job->meta['establishment_map'] ?? null);

        $catalogMaps = $this->syncCatalogMaps();
        $total = $this->connection->countItems();
        $chunkSize = $job->chunk_size ?: (int) config('facturador3_import.chunk_size', 1000);

        $job->update([
            'phase' => 'products',
            'status' => 'processing',
            'total_count' => $total,
            'processed_count' => 0,
            'progress' => 0,
            'started_at' => Carbon::now(),
            'meta' => array_merge($job->meta ?? [], [
                'establishment_map' => $establishmentMap,
                'category_map' => $catalogMaps['categories'],
                'brand_map' => $catalogMaps['brands'],
                'chunks_total' => 0,
                'chunks_done' => 0,
            ]),
        ]);

        $queue = config('facturador3_import.queue', 'imports');
        $lastId = 0;
        $chunks = 0;

        while (true) {
            $batch = $this->connection->connection()
                ->table('items')
                ->whereIn('item_type_id', ['01', '02'])
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($chunkSize)
                ->pluck('id');

            if ($batch->isEmpty()) {
                break;
            }

            $from = $lastId;
            $to = (int) $batch->last();
            ImportFacturador3ProductsChunk::dispatch($job->id, $from, $to)
                ->onQueue($queue);

            $lastId = $to;
            $chunks++;
        }

        $job->update([
            'meta' => array_merge($job->fresh()->meta ?? [], ['chunks_total' => $chunks]),
        ]);

        if ($chunks === 0) {
            $this->completeProductsPhase($job->fresh());
        }
    }

    public function dispatchStockImport(Facturador3ImportJob $job): void
    {
        $this->connection->configureFromJobMeta($job->meta);
        $establishmentMap = $this->resolveEstablishmentMap($job->meta['establishment_map'] ?? null);
        $skipZero = (bool) ($job->meta['skip_zero_stock'] ?? true);

        $this->clearPreviousImportKardex();

        $total = $this->connection->countStockRows($skipZero);
        $chunkSize = $job->chunk_size ?: (int) config('facturador3_import.chunk_size', 1000);

        $job->update([
            'phase' => 'stock',
            'status' => 'processing',
            'total_count' => $total,
            'processed_count' => 0,
            'progress' => 0,
            'last_processed_id' => 0,
            'started_at' => $job->started_at ?? Carbon::now(),
            'meta' => array_merge($job->meta ?? [], [
                'establishment_map' => $establishmentMap,
                'skip_zero_stock' => $skipZero,
                'chunks_total' => 0,
            ]),
        ]);

        $queue = config('facturador3_import.queue', 'imports');
        $lastId = 0;
        $chunks = 0;

        while (true) {
            $query = $this->connection->connection()
                ->table('item_warehouse')
                ->when($skipZero, fn ($q) => $q->where('stock', '<>', 0))
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($chunkSize);

            $batch = $query->pluck('id');

            if ($batch->isEmpty()) {
                break;
            }

            $from = $lastId;
            $to = (int) $batch->last();
            ImportFacturador3StockChunk::dispatch($job->id, $from, $to)
                ->onQueue($queue);

            $lastId = $to;
            $chunks++;
        }

        $job->update([
            'meta' => array_merge($job->fresh()->meta ?? [], ['chunks_total' => $chunks]),
        ]);

        if ($chunks === 0) {
            $job->update([
                'status' => 'completed',
                'progress' => 100,
                'completed_at' => Carbon::now(),
            ]);
        }
    }

    public function clearPreviousImportKardex(): int
    {
        return Kardex::where('document_entity', config('facturador3_import.kardex_document_entity'))->delete();
    }

    public function recalculateProductStock(array $productIds): void
    {
        if (empty($productIds)) {
            return;
        }

        $totals = DB::table('kardexes')
            ->select('product_id', DB::raw('SUM(quantity) as total_stock'))
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id')
            ->get();

        foreach ($totals as $row) {
            Product::where('id', $row->product_id)->update([
                'stock' => (int) round((float) $row->total_stock),
            ]);
        }
    }

    public function incrementJobProgress(Facturador3ImportJob $job, int $processedDelta, ?int $lastId = null): void
    {
        $updates = [
            'processed_count' => DB::raw('processed_count + '.max(0, $processedDelta)),
        ];

        if ($lastId !== null) {
            $updates['last_processed_id'] = $lastId;
        }

        Facturador3ImportJob::where('id', $job->id)->update($updates);
        $job->refresh();

        $total = max(1, (int) $job->total_count);
        $progress = min(99, (int) floor(((int) $job->processed_count / $total) * 100));

        $job->update(['progress' => $progress]);

        if ((int) $job->processed_count >= (int) $job->total_count) {
            $lock = Cache::lock('f3_import_complete_'.$job->id, 60);

            if ($lock->get()) {
                try {
                    $job->refresh();

                    if ((int) $job->processed_count < (int) $job->total_count) {
                        return;
                    }

                    if ($job->phase === 'products') {
                        $this->completeProductsPhase($job);

                        return;
                    }

                    $job->update([
                        'status' => 'completed',
                        'progress' => 100,
                        'completed_at' => Carbon::now(),
                    ]);
                } finally {
                    $lock->release();
                }
            }

            return;
        }
    }

    protected function completeProductsPhase(Facturador3ImportJob $job): void
    {
        if (($job->meta['auto_stock'] ?? false) && $job->phase === 'products') {
            $this->dispatchStockImport($job);

            return;
        }

        $job->update([
            'status' => 'completed',
            'progress' => 100,
            'completed_at' => Carbon::now(),
        ]);
    }

    public function markJobFailed(Facturador3ImportJob $job, string $message): void
    {
        $job->update([
            'status' => 'failed',
            'error_message' => $message,
            'completed_at' => Carbon::now(),
        ]);
    }

    public function importFiltersFromMeta(array $meta): array
    {
        return [
            'import_with_stock' => $meta['import_with_stock'] ?? true,
            'import_without_stock' => $meta['import_without_stock'] ?? true,
            'min_stock' => $meta['min_stock'] ?? null,
        ];
    }

    public function verifyStock(array $establishmentMap): array
    {
        $discrepancies = [];
        $skipZero = true;

        foreach ($establishmentMap as $f3EstablishmentId => $localId) {
            $warehouseIds = $this->connection->connection()
                ->table('warehouses')
                ->where('establishment_id', $f3EstablishmentId)
                ->pluck('id');

            if ($warehouseIds->isEmpty()) {
                continue;
            }

            $f3Rows = $this->connection->connection()
                ->table('item_warehouse as iw')
                ->whereIn('iw.warehouse_id', $warehouseIds)
                ->when($skipZero, fn ($q) => $q->where('iw.stock', '<>', 0))
                ->get(['iw.item_id', 'iw.stock']);

            foreach ($f3Rows as $f3Row) {
                $map = Facturador3ImportMap::where('f3_item_id', $f3Row->item_id)->first();
                if (! $map || ! $map->is_product) {
                    continue;
                }

                $baStock = (float) DB::table('kardexes')
                    ->where('product_id', $map->product_id)
                    ->where('local_id', $localId)
                    ->sum('quantity');

                $f3Stock = (float) $f3Row->stock;

                if (abs($baStock - $f3Stock) > 0.0001) {
                    $discrepancies[] = [
                        'f3_item_id' => $f3Row->item_id,
                        'product_id' => $map->product_id,
                        'interne' => $map->interne,
                        'local_id' => $localId,
                        'f3_stock' => $f3Stock,
                        'ba_stock' => $baStock,
                        'diff' => $baStock - $f3Stock,
                    ];
                }
            }
        }

        return $discrepancies;
    }
}
