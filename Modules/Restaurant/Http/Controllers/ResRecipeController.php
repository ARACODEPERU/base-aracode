<?php

namespace Modules\Restaurant\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Restaurant\Entities\ResComanda;
use Modules\Restaurant\Entities\ResRecipe;
use Modules\Restaurant\Entities\ResRecipeItem;
use Modules\Restaurant\Entities\ResSupply;

class ResRecipeController extends Controller
{
    public function show($comandaId)
    {
        $comanda = ResComanda::findOrFail($comandaId);
        $recipe = ResRecipe::with('items.supply')->firstOrCreate(['comanda_id' => $comandaId]);
        $supplies = ResSupply::where('status', true)->orderBy('name')->get(['id', 'name', 'unit']);
        $otherComandas = ResComanda::where('status', true)
            ->where('id', '!=', $comandaId)
            ->whereHas('recipe.items')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Restaurant::Comandas/Recipe', [
            'comanda' => $comanda,
            'recipe' => $recipe,
            'supplies' => $supplies,
            'otherComandas' => $otherComandas,
        ]);
    }

    public function update(Request $request, $comandaId)
    {
        $validated = $request->validate([
            'items' => 'present|array',
            'items.*.supply_id' => 'required|integer|exists:res_supplies,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
        ]);

        $comanda = ResComanda::findOrFail($comandaId);

        DB::beginTransaction();
        try {
            $recipe = ResRecipe::firstOrCreate(['comanda_id' => $comanda->id]);
            ResRecipeItem::where('recipe_id', $recipe->id)->delete();

            foreach ($validated['items'] as $item) {
                ResRecipeItem::create([
                    'recipe_id' => $recipe->id,
                    'supply_id' => $item['supply_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la receta: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Receta guardada correctamente',
        ]);
    }

    public function copyFrom(Request $request, $comandaId)
    {
        $validated = $request->validate([
            'source_comanda_id' => 'required|integer|exists:res_comandas,id',
        ]);

        $source = ResRecipe::with('items')->where('comanda_id', $validated['source_comanda_id'])->first();

        if (! $source || $source->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'El plato seleccionado no tiene receta configurada',
            ], 422);
        }

        $recipe = ResRecipe::firstOrCreate(['comanda_id' => $comandaId]);
        ResRecipeItem::where('recipe_id', $recipe->id)->delete();

        foreach ($source->items as $item) {
            ResRecipeItem::create([
                'recipe_id' => $recipe->id,
                'supply_id' => $item->supply_id,
                'quantity' => $item->quantity,
            ]);
        }

        $recipe->load('items.supply');

        return response()->json([
            'success' => true,
            'message' => 'Receta copiada correctamente',
            'recipe' => $recipe,
        ]);
    }
}
