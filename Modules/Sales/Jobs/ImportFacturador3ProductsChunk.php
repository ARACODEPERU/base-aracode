<?php

namespace Modules\Sales\Jobs;

use App\Models\Product;
use Carbon\Carbon;
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

class ImportFacturador3ProductsChunk implements ShouldQueue
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
    ): void {
        $job = Facturador3ImportJob::find($this->importJobId);

        if (! $job || $job->status === 'failed') {
            return;
        }

        $connection->configureFromJobMeta($job->meta);
        $meta = $job->meta ?? [];
        $filters = $importService->importFiltersFromMeta($meta);
        $excludedIds = $meta['excluded_item_ids'] ?? [];

        $mapper = new Facturador3ProductMapper(
            $meta['category_map'] ?? [],
            $meta['brand_map'] ?? [],
        );

        $items = $connection->connection()
            ->table('items')
            ->whereIn('item_type_id', ['01', '02'])
            ->where('id', '>', $this->fromId)
            ->where('id', '<=', $this->toId)
            ->orderBy('id')
            ->get();

        if ($items->isEmpty()) {
            $importService->incrementJobProgress($job, 0, $this->toId);

            return;
        }

        $stockSums = $connection->itemStockSums($items->pluck('id')->all());

        $existingMaps = Facturador3ImportMap::whereIn('f3_item_id', $items->pluck('id'))
            ->pluck('f3_item_id')
            ->flip();

        $rowsToInsert = [];
        $f3Meta = [];

        foreach ($items as $item) {
            $stockSum = (float) ($stockSums[$item->id] ?? 0);

            if (! $preview->shouldImportItem($stockSum, $filters, $excludedIds, (int) $item->id)) {
                continue;
            }

            if ($existingMaps->has($item->id)) {
                continue;
            }

            $interne = $mapper->resolveInterne($item);

            if (Product::where('interne', $interne)->exists()) {
                $existingProduct = Product::where('interne', $interne)->first();
                Facturador3ImportMap::updateOrCreate(
                    ['f3_item_id' => $item->id],
                    [
                        'product_id' => $existingProduct->id,
                        'interne' => $interne,
                        'is_product' => $mapper->isProduct($item),
                        'imported_at' => Carbon::now(),
                    ]
                );

                continue;
            }

            $rowsToInsert[] = $mapper->mapToProductRow($item);
            $f3Meta[] = [
                'f3_item_id' => $item->id,
                'interne' => $interne,
                'is_product' => $mapper->isProduct($item),
            ];
        }

        $processed = 0;

        if (! empty($rowsToInsert)) {
            DB::table('products')->insert($rowsToInsert);

            $internes = array_column($f3Meta, 'interne');
            $inserted = Product::whereIn('interne', $internes)->get(['id', 'interne'])->keyBy('interne');

            $mapRows = [];
            $now = Carbon::now();

            foreach ($f3Meta as $metaRow) {
                $product = $inserted->get($metaRow['interne']);
                if (! $product) {
                    continue;
                }

                $mapRows[] = [
                    'f3_item_id' => $metaRow['f3_item_id'],
                    'product_id' => $product->id,
                    'interne' => $metaRow['interne'],
                    'is_product' => $metaRow['is_product'] ? 1 : 0,
                    'imported_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($mapRows)) {
                DB::table('facturador3_import_maps')->insert($mapRows);
            }

            $processed = count($mapRows);
        }

        $importService->incrementJobProgress($job, $items->count(), $this->toId);

        Log::info("Facturador3 products chunk {$this->fromId}-{$this->toId}: inserted {$processed} products.");
    }

    public function failed(\Throwable $exception): void
    {
        $job = Facturador3ImportJob::find($this->importJobId);

        if ($job) {
            app(Facturador3ImportService::class)->markJobFailed($job, $exception->getMessage());
        }

        Log::error("Facturador3 products chunk failed: {$exception->getMessage()}");
    }
}
