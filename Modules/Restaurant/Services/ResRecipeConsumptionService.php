<?php

namespace Modules\Restaurant\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\Restaurant\Entities\ResComanda;
use Modules\Restaurant\Entities\ResRecipe;
use Modules\Restaurant\Entities\ResSale;
use Modules\Restaurant\Entities\ResSaleDetail;
use Modules\Restaurant\Entities\ResSupply;
use Modules\Restaurant\Entities\ResSupplyMovement;

class ResRecipeConsumptionService
{
    public function __construct(
        protected ResSupplyStockService $stockService
    ) {}

    /**
     * @return array{warnings: array<int, string>, insufficient: bool}
     */
    public function consumeForSale(ResSale $sale): array
    {
        $sale->load('details');
        $warnings = [];
        $insufficient = false;

        foreach ($sale->details as $detail) {
            $result = $this->consumeForDetail($detail, $sale->id);
            $warnings = array_merge($warnings, $result['warnings']);
            if ($result['insufficient']) {
                $insufficient = true;
            }
        }

        return ['warnings' => $warnings, 'insufficient' => $insufficient];
    }

    /**
     * @return array{warnings: array<int, string>, insufficient: bool}
     */
    public function consumeForDetail(ResSaleDetail $detail, int $saleId): array
    {
        $warnings = [];
        $insufficient = false;

        $recipe = ResRecipe::with('items.supply')
            ->where('comanda_id', $detail->comanda_id)
            ->first();

        if (! $recipe || $recipe->items->isEmpty()) {
            return ['warnings' => $warnings, 'insufficient' => false];
        }

        $portions = (float) $detail->quantity;

        foreach ($recipe->items as $item) {
            $supply = $item->supply;
            if (! $supply || ! $supply->status) {
                continue;
            }

            $needed = (float) $item->quantity * $portions;
            $available = (float) $supply->stock;
            $toDeduct = min($needed, max(0, $available));

            if ($toDeduct > 0) {
                $supply->decrement('stock', $toDeduct);
                ResSupplyMovement::create([
                    'supply_id' => $supply->id,
                    'type' => 'consumption',
                    'quantity' => -$toDeduct,
                    'reference_type' => ResSale::class,
                    'reference_id' => $saleId,
                    'notes' => "Venta #{$saleId}, comanda #{$detail->comanda_id}",
                    'user_id' => Auth::id(),
                    'local_id' => Auth::user()?->local_id,
                ]);
            }

            if ($toDeduct < $needed) {
                $insufficient = true;
                $warnings[] = "Stock insuficiente de {$supply->name}: faltaron " . round($needed - $toDeduct, 4) . " {$supply->unit}";
                ResSupplyMovement::create([
                    'supply_id' => $supply->id,
                    'type' => 'consumption',
                    'quantity' => 0,
                    'reference_type' => ResSale::class,
                    'reference_id' => $saleId,
                    'notes' => "Stock insuficiente: requerido {$needed} {$supply->unit}, disponible {$available}",
                    'user_id' => Auth::id(),
                    'local_id' => Auth::user()?->local_id,
                ]);
            }
        }

        return ['warnings' => $warnings, 'insufficient' => $insufficient];
    }

    public function voidSaleConsumption(int $saleId): void
    {
        $movements = ResSupplyMovement::where('reference_type', ResSale::class)
            ->where('reference_id', $saleId)
            ->where('type', 'consumption')
            ->where('quantity', '<', 0)
            ->get();

        foreach ($movements as $movement) {
            $qty = abs((float) $movement->quantity);
            if ($qty <= 0) {
                continue;
            }

            ResSupply::where('id', $movement->supply_id)->increment('stock', $qty);

            ResSupplyMovement::create([
                'supply_id' => $movement->supply_id,
                'type' => 'void_sale',
                'quantity' => $qty,
                'reference_type' => ResSale::class,
                'reference_id' => $saleId,
                'notes' => "Anulación venta #{$saleId}",
                'user_id' => Auth::id(),
                'local_id' => Auth::user()?->local_id,
            ]);
        }
    }

    public function countComandasWithoutRecipe(): int
    {
        $withRecipe = ResRecipe::whereHas('items')->pluck('comanda_id');

        return ResComanda::where('status', true)
            ->whereNotIn('id', $withRecipe)
            ->count();
    }

    public function getTopCriticalSupplies(int $limit = 5): Collection
    {
        return $this->stockService->getLowStock()->take($limit)->map(fn (ResSupply $s) => [
            'id' => $s->id,
            'name' => $s->name,
            'unit' => $s->unit,
            'stock' => (float) $s->stock,
            'stock_min' => (float) $s->stock_min,
        ]);
    }
}
