<?php

namespace Modules\Sales\Services\Facturador3;

use Carbon\Carbon;

class Facturador3ProductMapper
{
    public function __construct(
        protected array $categoryMap = [],
        protected array $brandMap = [],
    ) {}

    public function setCategoryMap(array $map): self
    {
        $this->categoryMap = $map;

        return $this;
    }

    public function setBrandMap(array $map): self
    {
        $this->brandMap = $map;

        return $this;
    }

    public function resolveInterne(object $item): string
    {
        if (! empty($item->internal_id)) {
            return (string) $item->internal_id;
        }

        if (! empty($item->item_code)) {
            return (string) $item->item_code;
        }

        return 'F3-'.$item->id;
    }

    public function resolveUsine(object $item): ?string
    {
        return $item->item_code_gs1 ?? $item->item_code ?? null;
    }

    public function isProduct(object $item): bool
    {
        return ($item->item_type_id ?? '01') === '01';
    }

    public function mapToProductRow(object $item): array
    {
        $interne = $this->resolveInterne($item);
        $salePrice = (float) ($item->sale_unit_price ?? 0);
        $now = Carbon::now();

        return [
            'usine' => $this->resolveUsine($item),
            'interne' => $interne,
            'description' => mb_substr((string) ($item->description ?? ''), 0, 300),
            'image' => config('facturador3_import.default_image'),
            'purchase_prices' => (float) ($item->purchase_unit_price ?? 0),
            'sale_prices' => json_encode([
                'high' => $salePrice,
                'medium' => $salePrice,
                'under' => $salePrice,
            ]),
            'sizes' => null,
            'stock_min' => max(0, (int) ($item->stock_min ?? 1)),
            'stock' => 0,
            'presentations' => 0,
            'is_product' => $this->isProduct($item) ? 1 : 0,
            'type_sale_affectation_id' => $item->sale_affectation_igv_type_id ?? '10',
            'type_purchase_affectation_id' => $item->purchase_affectation_igv_type_id ?? '10',
            'type_unit_measure_id' => $item->unit_type_id ?? 'NIU',
            'status' => ($item->active ?? true) ? 1 : 0,
            'category_id' => $this->categoryMap[$item->category_id ?? 0] ?? null,
            'brand_id' => $this->brandMap[$item->brand_id ?? 0] ?? null,
            'icbper' => $item->digemid ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    public function mapToKardexRow(int $productId, int $localId, float $quantity): array
    {
        $now = Carbon::now();

        return [
            'date_of_issue' => $now->format('Y-m-d'),
            'motion' => 'purchase',
            'product_id' => $productId,
            'local_id' => $localId,
            'quantity' => $quantity,
            'document_id' => null,
            'document_entity' => config('facturador3_import.kardex_document_entity'),
            'description' => config('facturador3_import.kardex_description'),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
