<?php

namespace Modules\Restaurant\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Restaurant\Entities\ResSupply;
use Modules\Restaurant\Entities\ResSupplyMovement;

class ResSupplyStockService
{
    public function addPurchase(
        int $supplyId,
        float $quantity,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): ResSupplyMovement {
        $supply = ResSupply::findOrFail($supplyId);
        $supply->increment('stock', $quantity);

        return ResSupplyMovement::create([
            'supply_id' => $supplyId,
            'type' => 'purchase',
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'user_id' => Auth::id(),
            'local_id' => Auth::user()?->local_id,
        ]);
    }

    public function adjust(int $supplyId, float $quantityDelta, ?string $notes = null): ResSupplyMovement
    {
        $supply = ResSupply::findOrFail($supplyId);
        $newStock = max(0, (float) $supply->stock + $quantityDelta);
        $supply->update(['stock' => $newStock]);

        return ResSupplyMovement::create([
            'supply_id' => $supplyId,
            'type' => 'adjustment',
            'quantity' => $quantityDelta,
            'notes' => $notes,
            'user_id' => Auth::id(),
            'local_id' => Auth::user()?->local_id,
        ]);
    }

    public function getLowStock()
    {
        return ResSupply::where('status', true)
            ->whereColumn('stock', '<=', 'stock_min')
            ->orderBy('stock')
            ->get();
    }

    public function getOutOfStockCount(): int
    {
        return ResSupply::where('status', true)->where('stock', '<=', 0)->count();
    }

    public function getLowStockCount(): int
    {
        return ResSupply::where('status', true)
            ->whereColumn('stock', '<=', 'stock_min')
            ->count();
    }

    public function getShoppingList()
    {
        return $this->getLowStock()->map(function (ResSupply $supply) {
            return [
                'id' => $supply->id,
                'name' => $supply->name,
                'unit' => $supply->unit,
                'stock' => (float) $supply->stock,
                'stock_min' => (float) $supply->stock_min,
                'suggested_qty' => round($supply->suggestedPurchaseQty(), 4),
                'status_label' => $supply->isOutOfStock() ? 'sin_stock' : 'bajo',
            ];
        });
    }
}
