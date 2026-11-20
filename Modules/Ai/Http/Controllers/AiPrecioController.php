<?php

namespace Modules\Ai\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Ai\Entities\AiModelPrice;
use Modules\Ai\Support\AiPricing;

/**
 * Precios editables por proveedor y modelo (USD por millón de tokens).
 */
class AiPrecioController extends Controller
{
    public function index()
    {
        $prices = AiModelPrice::query()
            ->orderBy('provider')
            ->orderBy('model')
            ->get()
            ->map(fn (AiModelPrice $price) => [
                'id' => $price->id,
                'provider' => $price->provider,
                'model' => $price->model,
                'input' => (float) $price->input_price_per_million,
                'output' => (float) $price->output_price_per_million,
                'currency' => $price->currency,
                'active' => (bool) $price->active,
            ])
            ->all();

        return Inertia::render('Ai::Precios/Index', [
            'prices' => $prices,
            'providers' => [
                ['value' => 'openai', 'label' => 'OpenAI'],
                ['value' => 'gemini', 'label' => 'Gemini AI'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);

        $price = AiModelPrice::query()->updateOrCreate(
            ['provider' => $data['provider'], 'model' => $data['model']],
            [
                'input_price_per_million' => $data['input'],
                'output_price_per_million' => $data['output'],
                'currency' => 'USD',
                'active' => (bool) ($data['active'] ?? true),
            ]
        );

        AiPricing::flush();

        return response()->json([
            'success' => true,
            'message' => 'El precio del modelo se guardó correctamente.',
            'price' => ['id' => $price->id],
        ]);
    }

    public function update(Request $request, AiModelPrice $price)
    {
        $data = $this->validar($request);

        $price->update([
            'provider' => $data['provider'],
            'model' => $data['model'],
            'input_price_per_million' => $data['input'],
            'output_price_per_million' => $data['output'],
            'active' => (bool) ($data['active'] ?? true),
        ]);

        AiPricing::flush();

        return response()->json([
            'success' => true,
            'message' => 'El precio del modelo se actualizó correctamente.',
        ]);
    }

    public function destroy(AiModelPrice $price)
    {
        $price->delete();
        AiPricing::flush();

        return response()->json([
            'success' => true,
            'message' => 'El precio del modelo se eliminó. Se usará la tarifa de respaldo.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request): array
    {
        return $request->validate([
            'provider' => 'required|in:openai,gemini',
            'model' => 'required|string|max:100',
            'input' => 'required|numeric|min:0',
            'output' => 'required|numeric|min:0',
            'active' => 'nullable|boolean',
        ]);
    }
}
