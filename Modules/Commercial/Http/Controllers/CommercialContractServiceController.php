<?php

namespace Modules\Commercial\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class CommercialContractServiceController extends Controller
{
    public function search(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $query = Product::query()
            ->where('is_product', false)
            ->orderBy('description');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('interne', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('usine', 'like', "%{$search}%");
            });
        }

        $services = $query->limit(20)->get()->map(fn (Product $product) => $this->formatService($product));

        return response()->json([
            'success' => true,
            'services' => $services,
        ]);
    }

    public function show(int $id)
    {
        $product = Product::where('is_product', false)->findOrFail($id);

        return response()->json([
            'success' => true,
            'service' => $this->formatService($product),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedServiceData($request);

        $product = Product::create([
            'usine' => $data['usine'] ?? null,
            'interne' => $data['interne'],
            'description' => $data['description'],
            'image' => 'img/imagen-no-disponible.jpg',
            'purchase_prices' => 0,
            'sale_prices' => json_encode($data['sale_prices']),
            'sizes' => null,
            'stock_min' => 1,
            'stock' => 1,
            'presentations' => false,
            'is_product' => false,
            'type_sale_affectation_id' => '10',
            'type_purchase_affectation_id' => '10',
            'type_unit_measure_id' => 'ZZ',
            'status' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Servicio registrado correctamente.',
            'service' => $this->formatService($product),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $product = Product::where('is_product', false)->findOrFail($id);
        $data = $this->validatedServiceData($request, $id);

        $product->update([
            'usine' => $data['usine'] ?? null,
            'interne' => $data['interne'],
            'description' => $data['description'],
            'sale_prices' => json_encode($data['sale_prices']),
            'status' => $request->boolean('status', true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Servicio actualizado correctamente.',
            'service' => $this->formatService($product->fresh()),
        ]);
    }

    private function validatedServiceData(Request $request, ?int $ignoreId = null): array
    {
        $uniqueRule = 'required|unique:products,interne';
        if ($ignoreId) {
            $uniqueRule .= ','.$ignoreId;
        }

        return $request->validate([
            'usine' => ['nullable', 'string', 'max:50'],
            'interne' => $uniqueRule,
            'description' => ['required', 'string', 'max:300'],
            'sale_prices' => ['required', 'array'],
            'sale_prices.high' => ['required', 'numeric', 'min:0'],
            'sale_prices.medium' => ['nullable', 'numeric', 'min:0'],
            'sale_prices.under' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'boolean'],
        ]);
    }

    private function formatService(Product $product): array
    {
        $salePrices = $product->sale_prices;
        if (is_string($salePrices)) {
            $salePrices = json_decode($salePrices, true) ?? [];
        }

        return [
            'id' => $product->id,
            'usine' => $product->usine,
            'interne' => $product->interne,
            'description' => $product->description,
            'sale_prices' => $salePrices,
            'status' => (bool) $product->status,
        ];
    }
}
