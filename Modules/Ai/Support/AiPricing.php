<?php

namespace Modules\Ai\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\Ai\Entities\AiModelPrice;

/**
 * Cálculo del costo estimado de una consulta.
 *
 * Usa la tarifa activa de `ai_model_prices` (editable desde el módulo). Si el
 * modelo no tiene fila, cae a las tarifas de respaldo de ai.prices para no
 * dejar el costo en cero.
 */
class AiPricing
{
    /**
     * Costo en dólares para la cantidad de tokens indicada.
     */
    public static function cost(string $provider, string $model, int $promptTokens, int $completionTokens): float
    {
        [$input, $output] = self::rates($provider, $model);

        $cost = ($promptTokens / 1_000_000) * $input
            + ($completionTokens / 1_000_000) * $output;

        return round($cost, 6);
    }

    /**
     * Tarifas por millón de tokens: [entrada, salida].
     *
     * @return array{0: float, 1: float}
     */
    public static function rates(string $provider, string $model): array
    {
        // OpenAI devuelve el modelo con la fecha de la version
        // (gpt-4.1-mini-2025-04-14). La tarifa se carga por el nombre corto,
        // asi que se prueba tambien sin ese sufijo antes de darla por ausente.
        foreach (array_unique([$model, self::sinFecha($model)]) as $candidato) {
            $price = self::storedPrice($provider, $candidato);

            if ($price !== null) {
                return [$price['input'], $price['output']];
            }

            $fallback = config('ai.prices.' . $provider . '.' . $candidato);

            if (is_array($fallback)) {
                return [(float) ($fallback['input'] ?? 0), (float) ($fallback['output'] ?? 0)];
            }
        }

        return [0.0, 0.0];
    }

    /**
     * Quita el sufijo de fecha que agrega el proveedor al nombre del modelo.
     */
    private static function sinFecha(string $model): string
    {
        return preg_replace('/-\d{4}-\d{2}-\d{2}$/', '', $model) ?? $model;
    }

    /**
     * @return array{input: float, output: float}|null
     */
    private static function storedPrice(string $provider, string $model): ?array
    {
        if (! Schema::hasTable('ai_model_prices')) {
            return null;
        }

        $key = 'ai:price:' . $provider . ':' . $model;

        return Cache::remember($key, now()->addMinutes(10), function () use ($provider, $model) {
            $row = AiModelPrice::query()
                ->where('provider', $provider)
                ->where('model', $model)
                ->where('active', true)
                ->first();

            if ($row === null) {
                return null;
            }

            return [
                'input' => (float) $row->input_price_per_million,
                'output' => (float) $row->output_price_per_million,
            ];
        });
    }

    /**
     * Limpia la caché de tarifas (al editar los precios).
     */
    public static function flush(): void
    {
        foreach (['openai', 'gemini'] as $provider) {
            foreach (array_keys((array) config('ai.prices.' . $provider, [])) as $model) {
                Cache::forget('ai:price:' . $provider . ':' . $model);
            }
        }
    }
}
