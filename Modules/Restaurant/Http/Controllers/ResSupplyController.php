<?php

namespace Modules\Restaurant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Restaurant\Entities\ResSupply;
use Modules\Restaurant\Entities\ResSupplyMovement;
use Modules\Restaurant\Services\ResSupplyStockService;

class ResSupplyController extends Controller
{
    protected int $RPTABLE;

    public function __construct(
        protected ResSupplyStockService $stockService
    ) {
        $this->RPTABLE = (int) (env('RECORDS_PAGE_TABLE') ?? 10);
    }

    public function index()
    {
        $query = ResSupply::query();

        if (request()->filled('search')) {
            $query->where('name', 'like', '%' . request()->input('search') . '%');
        }

        if (request()->filled('stock_filter')) {
            match (request()->input('stock_filter')) {
                'low' => $query->whereColumn('stock', '<=', 'stock_min'),
                'out' => $query->where('stock', '<=', 0),
                'ok' => $query->whereColumn('stock', '>', 'stock_min'),
                default => null,
            };
        }

        $supplies = $query->orderByDesc('id')->paginate($this->RPTABLE)->onEachSide(2);

        return Inertia::render('Restaurant::Supplies/List', [
            'supplies' => $supplies,
            'filters' => request()->all('search', 'stock_filter'),
            'units' => ResSupply::UNITS,
        ]);
    }

    public function create()
    {
        return Inertia::render('Restaurant::Supplies/Create', [
            'units' => ResSupply::UNITS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|in:' . implode(',', ResSupply::UNITS),
            'stock' => 'nullable|numeric|min:0',
            'stock_min' => 'nullable|numeric|min:0',
            'status' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $initialStock = (float) ($validated['stock'] ?? 0);

            $supply = ResSupply::create([
                'name' => $validated['name'],
                'unit' => $validated['unit'],
                'stock' => 0,
                'stock_min' => $validated['stock_min'] ?? 1,
                'status' => $request->boolean('status', true),
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($initialStock > 0) {
                $this->stockService->addPurchase(
                    $supply->id,
                    $initialStock,
                    'Stock inicial al crear insumo'
                );
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('res_supplies_list')->with('success', 'Insumo registrado correctamente');
    }

    public function edit($id)
    {
        $supply = ResSupply::findOrFail($id);
        $movements = ResSupplyMovement::with('user')
            ->where('supply_id', $id)
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return Inertia::render('Restaurant::Supplies/Edit', [
            'supply' => $supply,
            'movements' => $movements,
            'units' => ResSupply::UNITS,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:res_supplies,id',
            'name' => 'required|string|max:255',
            'unit' => 'required|in:' . implode(',', ResSupply::UNITS),
            'stock_min' => 'nullable|numeric|min:0',
            'status' => 'boolean',
            'notes' => 'nullable|string',
            'adjust_qty' => 'nullable|numeric',
            'adjust_notes' => 'nullable|string',
        ]);

        $supply = ResSupply::findOrFail($validated['id']);

        $supply->update([
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'stock_min' => $validated['stock_min'] ?? $supply->stock_min,
            'status' => $request->boolean('status', $supply->status),
            'notes' => $validated['notes'] ?? null,
        ]);

        if (isset($validated['adjust_qty']) && (float) $validated['adjust_qty'] !== 0.0) {
            $this->stockService->adjust(
                $supply->id,
                (float) $validated['adjust_qty'],
                $validated['adjust_notes'] ?? 'Ajuste manual'
            );
        }

        return redirect()->route('res_supplies_list')->with('success', 'Insumo actualizado correctamente');
    }

    public function destroy($id)
    {
        try {
            $supply = ResSupply::findOrFail($id);
            $supply->delete();

            return response()->json([
                'success' => true,
                'message' => 'Insumo eliminado correctamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function search(Request $request)
    {
        $search = $request->input('q', '');

        $supplies = ResSupply::where('status', true)
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'unit', 'stock']);

        return response()->json(['supplies' => $supplies]);
    }

    public function shoppingList()
    {
        return Inertia::render('Restaurant::Supplies/ShoppingList', [
            'items' => $this->stockService->getShoppingList(),
        ]);
    }
}
