<?php

namespace Modules\Treasury\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Treasury\Entities\TreasuryCategory;

class TreasuryCategoryController extends Controller
{
    use ValidatesTreasuryRequests;

    public function index()
    {
        $this->authorizePermission('treasury_categorias');

        $categories = TreasuryCategory::query()
            ->withCount('transactions')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(fn (TreasuryCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'applies_to' => $category->applies_to,
                'is_system' => $category->is_system,
                'color' => $category->color,
                'transactions_count' => $category->transactions_count,
            ]);

        return Inertia::render('Treasury::Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('treasury_categorias');

        $data = $this->validated($request);
        $data['is_system'] = false;

        TreasuryCategory::create($data);

        return back()->with('success', 'Categoría creada.');
    }

    public function update(Request $request, int $id)
    {
        $this->authorizePermission('treasury_categorias');

        $category = TreasuryCategory::findOrFail($id);

        // Las del sistema solo permiten cambiar el color
        if ($category->is_system) {
            $request->validate(['color' => ['nullable', 'string', 'max:9']]);
            $category->color = $request->get('color');
            $category->save();

            return back()->with('success', 'Color actualizado.');
        }

        $category->fill($this->validated($request))->save();

        return back()->with('success', 'Categoría actualizada.');
    }

    public function destroy(int $id)
    {
        $this->authorizePermission('treasury_categorias');

        $category = TreasuryCategory::findOrFail($id);

        if ($category->is_system) {
            return back()->with('error', 'Las categorías del sistema no se pueden eliminar.');
        }

        if ($category->transactions()->exists()) {
            return back()->with('error', 'La categoría tiene movimientos asociados.');
        }

        $category->delete();

        return back()->with('success', 'Categoría eliminada.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'applies_to' => ['required', 'in:income,expense,both'],
            'color' => ['nullable', 'string', 'max:9'],
        ]);
    }
}
