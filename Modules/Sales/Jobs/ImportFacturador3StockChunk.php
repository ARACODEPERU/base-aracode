<?php

namespace Modules\Sales\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Sales\Entities\Facturador3ImportJob;
use Modules\Sales\Entities\Facturador3ImportMap;
use Modules\Sales\Services\Facturador3\Facturador3ConnectionService;
use Modules\Sales\Services\Facturador3\Facturador3ImportService;
use Modules\Sales\Services\Facturador3\Facturador3PreviewService;
use Modules\Sales\Services\Facturador3\Facturador3ProductMapper;

class ImportFacturador3StockChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(
        public int $importJobId,
        public int $fromId,
        public int $toId,
    ) {}

    public function handle(
        Facturador3ConnectionService $connection,
        Facturador3ImportService $importService,
        Facturador3PreviewService $preview,
        Facturador3ProductMapper $mapper,
    ): void {
        $job = Facturador3ImportJob::find($this->importJobId);

        if (! $job || $job->status === 'failed') {
            return;
        }

        $connection->configureFromJobMeta($job->meta);
        $meta = $job->meta ?? [];
        $establishmentMap = $meta['establishment_map'] ?? [];
        $skipZero = (bool) ($meta['skip_zero_stock'] ?? true);
        $filters = $importService->importFiltersFromMeta($meta);
        $excludedIds = $meta['excluded_item_ids'] ?? [];

        $rows = $connection->connection()
            ->table('item_warehouse as iw')
            ->join('warehouses as w', 'w.id', '=', 'iw.warehouse_id')
            ->where('iw.id', '>', $this->fromId)
            ->where('iw.id', '<=', $this->toId)
            ->when($skipZero, fn ($q) => $q->where('iw.stock', '<>', 0))
            ->orderBy('iw.id')
            ->get(['iw.id', 'iw.item_id', 'iw.stock', 'w.establishment_id']);

        if ($rows->isEmpty()) {
            $importService->incrementJobProgress($job, 0, $this->toId);

            return;
        }

        $itemIds = $rows->pluck('item_id')->unique()->values()->all();
        $stockSums = $connection->itemStockSums($itemIds);

        $maps = Facturador3ImportMap::whereIn('f3_item_id', $itemIds)
            ->where('is_product', true)
            ->get()
            ->keyBy('f3_item_id');

        $kardexRows = [];
        $productIds = [];

        foreach ($rows as $row) {
            if (in_array((int) $row->item_id, $excludedIds, true)) {
                continue;
            }

            $stockSum = (float) ($stockSums[$row->item_id] ?? 0);
            if (! $preview->shouldImportItem($stockSum, $filters, $excludedIds, (int) $row->item_id)) {
                continue;
            }

            $map = $maps->get($row->item_id);
            if (! $map) {
                continue;
            }

            $localId = $establishmentMap[$row->establishment_id] ?? null;
            if (! $localId) {
                continue;
            }

            $quantity = (float) $row->stock;
            if ($quantity == 0.0) {
                continue;
            }

            $kardexRows[] = $mapper->mapToKardexRow($map->product_id, (int) $localId, $quantity);
            $productIds[$map->product_id] = $map->product_id;
        }

        if (! empty($kardexRows)) {
            foreach (array_chunk($kardexRows, 500) as $chunk) {
                DB::table('kardexes')->insert($chunk);
            }

            $importService->recalculateProductStock(array_values($productIds));
        }

        $importService->incrementJobProgress($job, $rows->count(), $this->toId);

        Log::info("Facturador3 stock chunk {$this->fromId}-{$this->toId}: inserted ".count($kardexRows).' kardex rows.');
    }

    public function failed(\Throwable $exception): void
    {
        $job = Facturador3ImportJob::find($this->importJobId);

        if ($job) {
            app(Facturador3ImportService::class)->markJobFailed($job, $exception->getMessage());
        }

        Log::error("Facturador3 stock chunk failed: {$exception->getMessage()}");
    }
}
