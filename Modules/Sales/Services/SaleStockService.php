<?php

namespace Modules\Sales\Services;

use App\Models\Kardex;
use App\Models\KardexSize;
use App\Models\Product;
use App\Models\SaleProduct;
use Carbon\Carbon;

class SaleStockService
{
    public function recordOutbound(
        Product $product,
        float $quantity,
        int $documentId,
        string $documentEntity,
        int $localId,
        ?string $size = null,
        string $description = 'Venta'
    ): ?Kardex {
        if (! $product->is_product || $quantity <= 0) {
            return null;
        }

        $k = Kardex::create([
            'date_of_issue' => Carbon::now()->format('Y-m-d'),
            'motion' => 'sale',
            'product_id' => $product->id,
            'local_id' => $localId,
            'quantity' => $quantity * (-1),
            'document_id' => $documentId,
            'document_entity' => $documentEntity,
            'description' => $description,
        ]);

        if ($product->presentations && $size) {
            KardexSize::create([
                'kardex_id' => $k->id,
                'product_id' => $product->id,
                'local_id' => $localId,
                'size' => $size,
                'quantity' => $quantity * (-1),
            ]);

            $this->adjustProductSizes($product, $size, -$quantity);
        }

        $product->decrement('stock', $quantity);

        return $k;
    }

    public function recordOutboundByProductId(
        int $productId,
        float $quantity,
        int $documentId,
        string $documentEntity,
        int $localId,
        ?string $size = null,
        string $description = 'Venta'
    ): ?Kardex {
        $product = Product::find($productId);

        if (! $product) {
            return null;
        }

        return $this->recordOutbound(
            $product,
            $quantity,
            $documentId,
            $documentEntity,
            $localId,
            $size,
            $description
        );
    }

    public function recordReversal(
        Product $product,
        float $quantity,
        int $documentId,
        string $documentEntity,
        int $localId,
        ?string $size = null,
        string $description = 'Anulacion de Venta'
    ): ?Kardex {
        if (! $product->is_product || $quantity <= 0) {
            return null;
        }

        $k = Kardex::create([
            'date_of_issue' => Carbon::now()->format('Y-m-d'),
            'motion' => 'sale',
            'product_id' => $product->id,
            'local_id' => $localId,
            'quantity' => $quantity,
            'document_id' => $documentId,
            'document_entity' => $documentEntity,
            'description' => $description,
        ]);

        if ($product->presentations && $size) {
            KardexSize::create([
                'kardex_id' => $k->id,
                'product_id' => $product->id,
                'local_id' => $localId,
                'size' => $size,
                'quantity' => $quantity,
            ]);

            $this->adjustProductSizes($product, $size, $quantity);
        }

        $product->increment('stock', $quantity);

        return $k;
    }

    /**
     * @param  iterable<int, SaleProduct>  $saleProducts
     */
    public function reverseSaleProducts(
        iterable $saleProducts,
        int $localId,
        int $documentId,
        string $documentEntity,
        string $description = 'Anulacion de Venta'
    ): void {
        foreach ($saleProducts as $item) {
            $this->reverseSaleProductLine($item, $localId, $documentId, $documentEntity, $description);
        }
    }

    public function reverseSaleProductLine(
        SaleProduct $item,
        int $localId,
        int $documentId,
        string $documentEntity,
        string $description = 'Anulacion de Venta'
    ): void {
        if ($item->entity_name_product && $item->entity_name_product !== Product::class) {
            return;
        }

        $product = Product::find($item->product_id);
        if (! $product || ! $product->is_product) {
            return;
        }

        $saleProduct = json_decode($item->saleProduct ?? '{}');
        if (($saleProduct->unit_type ?? null) === 'ZZ') {
            return;
        }

        $size = $saleProduct->size ?? null;

        $this->recordReversal(
            $product,
            (float) $item->quantity,
            $documentId,
            $documentEntity,
            $localId,
            $size,
            $description
        );
    }

    private function adjustProductSizes(Product $product, string $size, float $delta): void
    {
        $tallas = json_decode($product->sizes, true) ?? [];
        if (! is_array($tallas)) {
            return;
        }

        $n_tallas = [];
        foreach ($tallas as $index => $talla) {
            if (($talla['size'] ?? '') == $size) {
                $n_tallas[$index] = [
                    'size' => $talla['size'],
                    'quantity' => ($talla['quantity'] + $delta),
                ];
            } else {
                $n_tallas[$index] = [
                    'size' => $talla['size'],
                    'quantity' => $talla['quantity'],
                ];
            }
        }

        $product->update([
            'sizes' => json_encode($n_tallas),
        ]);
    }
}
