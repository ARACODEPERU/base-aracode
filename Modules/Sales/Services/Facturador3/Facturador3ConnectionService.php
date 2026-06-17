<?php

namespace Modules\Sales\Services\Facturador3;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class Facturador3ConnectionService
{
    public function connectionName(): string
    {
        return config('facturador3_import.connection', 'facturador3_tenant');
    }

    public function connection()
    {
        return DB::connection($this->connectionName());
    }

    public function configureTenantDatabase(string $database): void
    {
        $database = trim($database);

        if ($database === '' || ! preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
            throw new RuntimeException('Nombre de base de datos inválido. Use solo letras, números y guión bajo.');
        }

        config(['database.connections.'.$this->connectionName().'.database' => $database]);
        DB::purge($this->connectionName());
    }

    public function configureFromJobMeta(?array $meta): void
    {
        $database = $meta['tenant_database'] ?? null;

        if (! empty($database)) {
            $this->configureTenantDatabase($database);

            return;
        }

        $this->assertConfigured();
    }

    public function getConfiguredDatabase(): ?string
    {
        $database = config('database.connections.'.$this->connectionName().'.database');

        return $database !== '' && $database !== null ? (string) $database : null;
    }

    public function assertConfigured(): void
    {
        if (empty($this->getConfiguredDatabase())) {
            throw new RuntimeException('Debe indicar la base de datos del tenant Facturador3.');
        }
    }

    public function testConnection(): bool
    {
        $this->assertConfigured();
        $this->connection()->getPdo();

        return true;
    }

    public function requiredTablesExist(): array
    {
        $this->assertConfigured();

        $required = ['items', 'item_warehouse', 'warehouses', 'establishments', 'categories', 'brands'];
        $missing = [];

        foreach ($required as $table) {
            if (! $this->connection()->getSchemaBuilder()->hasTable($table)) {
                $missing[] = $table;
            }
        }

        return $missing;
    }

    public function getEstablishments(): array
    {
        return $this->connection()
            ->table('establishments')
            ->orderBy('id')
            ->get(['id', 'description', 'code'])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function countItems(): int
    {
        return (int) $this->connection()
            ->table('items')
            ->whereIn('item_type_id', ['01', '02'])
            ->count();
    }

    public function countStockRows(bool $skipZero = true): int
    {
        $query = $this->connection()
            ->table('item_warehouse as iw')
            ->join('warehouses as w', 'w.id', '=', 'iw.warehouse_id');

        if ($skipZero) {
            $query->where('iw.stock', '<>', 0);
        }

        return (int) $query->count();
    }

    public function itemStockSums(array $itemIds): array
    {
        if (empty($itemIds)) {
            return [];
        }

        return $this->connection()
            ->table('item_warehouse')
            ->whereIn('item_id', $itemIds)
            ->groupBy('item_id')
            ->selectRaw('item_id, COALESCE(SUM(stock), 0) as stock_sum')
            ->pluck('stock_sum', 'item_id')
            ->map(fn ($value) => (float) $value)
            ->all();
    }
}
