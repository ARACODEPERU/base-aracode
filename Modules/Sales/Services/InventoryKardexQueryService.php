<?php

namespace Modules\Sales\Services;

use App\Models\Kardex;
use Illuminate\Database\Eloquent\Builder;

class InventoryKardexQueryService
{
    public function normalizeFilters(array $filters): array
    {
        return [
            'local_id' => (int) ($filters['local_id'] ?? 0),
            'product_id' => (int) ($filters['product_id'] ?? 0),
            'size' => $filters['size'] ?? '',
            'date_from' => $filters['date_from'] ?? '',
            'date_to' => $filters['date_to'] ?? '',
        ];
    }

    public function buildQuery(array $filters): Builder
    {
        $filters = $this->normalizeFilters($filters);

        $query = Kardex::query()
            ->join('products', 'kardexes.product_id', '=', 'products.id')
            ->leftJoin('local_sales', 'kardexes.local_id', '=', 'local_sales.id')
            ->leftJoin('kardex_sizes', 'kardex_sizes.kardex_id', '=', 'kardexes.id')
            ->where('products.is_product', true)
            ->select([
                'kardexes.id',
                'kardexes.date_of_issue',
                'kardexes.motion',
                'kardexes.quantity',
                'kardexes.description',
                'kardexes.created_at',
                'products.id as product_id',
                'products.interne',
                'products.description as product_description',
                'local_sales.description as local_name',
                'kardex_sizes.size',
            ]);

        if ($filters['local_id'] > 0) {
            $query->where('kardexes.local_id', $filters['local_id']);
        }

        if ($filters['product_id'] > 0) {
            $query->where('kardexes.product_id', $filters['product_id']);
        }

        if ($filters['size'] !== null && $filters['size'] !== '') {
            $query->where('kardex_sizes.size', $filters['size']);
        }

        if ($filters['date_from']) {
            $query->whereDate('kardexes.date_of_issue', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate('kardexes.date_of_issue', '<=', $filters['date_to']);
        }

        return $query
            ->orderByDesc('kardexes.date_of_issue')
            ->orderByDesc('kardexes.id');
    }

    public function count(array $filters): int
    {
        return $this->buildQuery($filters)->count();
    }

    public function cursor(array $filters)
    {
        return $this->buildQuery($filters)->cursor();
    }

    public function mapRow(object $row): array
    {
        return [
            'id' => $row->id,
            'date_of_issue' => $row->date_of_issue,
            'motion' => $row->motion,
            'motion_label' => $row->motion === 'purchase' ? 'Ingreso' : 'Salida',
            'quantity' => (float) $row->quantity,
            'description' => $row->description,
            'local_name' => $row->local_name ?? '—',
            'product_id' => $row->product_id,
            'interne' => $row->interne,
            'product_description' => $row->product_description,
            'size' => $row->size,
            'created_at' => $row->created_at,
        ];
    }
}
