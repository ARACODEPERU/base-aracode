<?php

namespace Modules\Sales\Services\Facturador3;

use Modules\Sales\Entities\Facturador3ImportExclusion;

class Facturador3PreviewService
{
    public function __construct(
        protected Facturador3ConnectionService $connection,
    ) {}

    public function summary(string $tenantDatabase, array $filters = []): array
    {
        $this->connection->configureTenantDatabase($tenantDatabase);
        $this->connection->assertConfigured();

        $excludedIds = $this->resolveExcludedIds($tenantDatabase, $filters['excluded_item_ids'] ?? []);
        $importWithStock = $filters['import_with_stock'] ?? true;
        $importWithoutStock = $filters['import_without_stock'] ?? true;
        $minStock = isset($filters['min_stock']) && $filters['min_stock'] !== '' && $filters['min_stock'] !== null
            ? (float) $filters['min_stock']
            : null;

        $row = $this->connection->connection()
            ->table('items as i')
            ->leftJoinSub(
                $this->connection->connection()
                    ->table('item_warehouse')
                    ->selectRaw('item_id, COALESCE(SUM(stock), 0) as stock_sum')
                    ->groupBy('item_id'),
                'stock_totals',
                'stock_totals.item_id',
                '=',
                'i.id'
            )
            ->whereIn('i.item_type_id', ['01', '02'])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN COALESCE(stock_totals.stock_sum, 0) >= 1 THEN 1 ELSE 0 END) as with_stock,
                SUM(CASE WHEN COALESCE(stock_totals.stock_sum, 0) = 0 THEN 1 ELSE 0 END) as without_stock
            ')
            ->first();

        $importable = $this->countImportable($importWithStock, $importWithoutStock, $minStock, $excludedIds);

        return [
            'tenant_database' => $tenantDatabase,
            'total' => (int) ($row->total ?? 0),
            'with_stock' => (int) ($row->with_stock ?? 0),
            'without_stock' => (int) ($row->without_stock ?? 0),
            'importable' => $importable,
            'excluded_count' => count($excludedIds),
        ];
    }

    public function search(string $tenantDatabase, string $search, int $limit = 20): array
    {
        $this->connection->configureTenantDatabase($tenantDatabase);
        $this->connection->assertConfigured();

        $search = trim($search);
        if ($search === '') {
            return [];
        }

        $items = $this->connection->connection()
            ->table('items as i')
            ->leftJoinSub(
                $this->connection->connection()
                    ->table('item_warehouse')
                    ->selectRaw('item_id, COALESCE(SUM(stock), 0) as stock_sum')
                    ->groupBy('item_id'),
                'stock_totals',
                'stock_totals.item_id',
                '=',
                'i.id'
            )
            ->whereIn('i.item_type_id', ['01', '02'])
            ->where(function ($query) use ($search) {
                $query->where('i.description', 'like', '%'.$search.'%')
                    ->orWhere('i.internal_id', 'like', '%'.$search.'%')
                    ->orWhere('i.item_code', 'like', '%'.$search.'%');
            })
            ->orderBy('i.id')
            ->limit(min(50, max(1, $limit)))
            ->get([
                'i.id',
                'i.description',
                'i.internal_id',
                'i.item_code',
            ]);

        $stockSums = $this->connection->itemStockSums($items->pluck('id')->all());

        return $items->map(function ($item) use ($stockSums) {
            $stock = (float) ($stockSums[$item->id] ?? 0);

            return [
                'id' => $item->id,
                'description' => $item->description,
                'internal_id' => $item->internal_id,
                'item_code' => $item->item_code,
                'stock_sum' => $stock,
            ];
        })->all();
    }

    public function shouldImportItem(
        float $stockSum,
        array $filters,
        array $excludedIds,
        ?int $itemId = null,
    ): bool {
        if ($itemId !== null && in_array($itemId, $excludedIds, true)) {
            return false;
        }

        $importWithStock = $filters['import_with_stock'] ?? true;
        $importWithoutStock = $filters['import_without_stock'] ?? true;
        $minStock = isset($filters['min_stock']) && $filters['min_stock'] !== '' && $filters['min_stock'] !== null
            ? (float) $filters['min_stock']
            : null;

        $hasStock = $stockSum >= 1;
        $noStock = $stockSum == 0.0;

        if ($hasStock && ! $importWithStock) {
            return false;
        }

        if ($noStock && ! $importWithoutStock) {
            return false;
        }

        if (! $hasStock && ! $noStock) {
            if (! $importWithStock) {
                return false;
            }
        }

        if ($hasStock && $minStock !== null && $stockSum < $minStock) {
            return false;
        }

        return $hasStock || $noStock;
    }

    public function resolveExcludedIds(string $tenantDatabase, array $extraIds = []): array
    {
        $stored = Facturador3ImportExclusion::query()
            ->where('tenant_database', $tenantDatabase)
            ->pluck('f3_item_id')
            ->all();

        return array_values(array_unique(array_map('intval', array_merge($stored, $extraIds))));
    }

    public function syncExclusions(string $tenantDatabase, int $userId, array $itemIds): void
    {
        $itemIds = array_values(array_unique(array_map('intval', $itemIds)));

        foreach ($itemIds as $itemId) {
            Facturador3ImportExclusion::firstOrCreate(
                [
                    'tenant_database' => $tenantDatabase,
                    'f3_item_id' => $itemId,
                ],
                ['user_id' => $userId]
            );
        }
    }

    public function removeExclusion(string $tenantDatabase, int $itemId): void
    {
        Facturador3ImportExclusion::query()
            ->where('tenant_database', $tenantDatabase)
            ->where('f3_item_id', $itemId)
            ->delete();
    }

    public function listExclusions(string $tenantDatabase, int $limit = 100): array
    {
        return Facturador3ImportExclusion::query()
            ->where('tenant_database', $tenantDatabase)
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['f3_item_id'])
            ->pluck('f3_item_id')
            ->all();
    }

    protected function countImportable(
        bool $importWithStock,
        bool $importWithoutStock,
        ?float $minStock,
        array $excludedIds,
    ): int {
        $filters = [
            'import_with_stock' => $importWithStock,
            'import_without_stock' => $importWithoutStock,
            'min_stock' => $minStock,
        ];

        $query = $this->connection->connection()
            ->table('items as i')
            ->leftJoinSub(
                $this->connection->connection()
                    ->table('item_warehouse')
                    ->selectRaw('item_id, COALESCE(SUM(stock), 0) as stock_sum')
                    ->groupBy('item_id'),
                'stock_totals',
                'stock_totals.item_id',
                '=',
                'i.id'
            )
            ->whereIn('i.item_type_id', ['01', '02']);

        if (! empty($excludedIds)) {
            $query->whereNotIn('i.id', $excludedIds);
        }

        if ($importWithStock && ! $importWithoutStock) {
            $query->whereRaw('COALESCE(stock_totals.stock_sum, 0) >= 1');
            if ($minStock !== null) {
                $query->whereRaw('COALESCE(stock_totals.stock_sum, 0) >= ?', [$minStock]);
            }
        } elseif (! $importWithStock && $importWithoutStock) {
            $query->whereRaw('COALESCE(stock_totals.stock_sum, 0) = 0');
        } elseif ($importWithStock && $importWithoutStock) {
            if ($minStock !== null) {
                $query->where(function ($inner) use ($minStock) {
                    $inner->whereRaw('COALESCE(stock_totals.stock_sum, 0) = 0')
                        ->orWhereRaw('COALESCE(stock_totals.stock_sum, 0) >= ?', [$minStock]);
                });
            }
        } else {
            return 0;
        }

        return (int) $query->count();
    }
}
