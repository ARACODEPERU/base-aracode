<?php

namespace Modules\Restaurant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Restaurant\Entities\ResSupply;
use Modules\Restaurant\Services\ResSupplyStockService;

class ResSupplyPurchaseController extends Controller
{
    public function __construct(
        protected ResSupplyStockService $stockService
    ) {}

    public function create()
    {
        $supplies = ResSupply::where('status', true)->orderBy('name')->get(['id', 'name', 'unit', 'stock']);

        return Inertia::render('Restaurant::Supplies/Purchase', [
            'supplies' => $supplies,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.supply_id' => 'required|integer|exists:res_supplies,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.notes' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['items'] as $item) {
                $lineNote = $item['notes'] ?? $validated['notes'] ?? null;
                $this->stockService->addPurchase(
                    (int) $item['supply_id'],
                    (float) $item['quantity'],
                    $lineNote
                );
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la compra: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Compra registrada correctamente',
        ]);
    }
}
