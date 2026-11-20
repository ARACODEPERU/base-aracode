<?php

namespace Modules\Ai\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Ai\Entities\AiUsage;
use Modules\Ai\Support\AiPricing;
use Modules\Ai\Support\AiResult;
use Modules\Ai\Support\IaSettings;
use Throwable;

/**
 * Guarda cada consulta de IA para las estadísticas de consumo.
 *
 * El registro nunca debe romper la respuesta al usuario: si falla la escritura
 * se anota en el log y se continúa.
 */
class AiUsageRecorder
{
    public function record(
        int|string|null $userId,
        AiResult $result,
        ?string $screen = null,
        ?string $prompt = null
    ): ?AiUsage {
        return $this->recordTokens(
            $userId,
            $result->provider,
            $result->model,
            $result->promptTokens,
            $result->completionTokens,
            $screen,
            'ok',
            null,
            $prompt,
            $result->totalTokens,
            $result->costUsd
        );
    }

    /**
     * Registra un consumo a partir de los tokens crudos.
     */
    public function recordTokens(
        int|string|null $userId,
        string $provider,
        string $model,
        int $promptTokens,
        int $completionTokens,
        ?string $screen = null,
        string $status = 'ok',
        ?string $error = null,
        ?string $prompt = null,
        ?int $totalTokens = null,
        ?float $costUsd = null
    ): ?AiUsage {
        if (! Schema::hasTable('ai_usages')) {
            return null;
        }

        $promptTokens = max(0, $promptTokens);
        $completionTokens = max(0, $completionTokens);
        $totalTokens = $totalTokens !== null && $totalTokens > 0
            ? $totalTokens
            : $promptTokens + $completionTokens;

        $costUsd ??= AiPricing::cost($provider, $model, $promptTokens, $completionTokens);

        try {
            return AiUsage::create([
                'user_id' => is_numeric($userId) ? (int) $userId : null,
                'provider' => $provider,
                'model' => $model,
                'screen' => $screen,
                'status' => $status,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'cost_usd' => $costUsd,
                'prompt_excerpt' => $this->excerpt($prompt),
                'error' => $error !== null ? Str::limit($error, 490) : null,
            ]);
        } catch (Throwable $exception) {
            Log::warning('No se pudo registrar el consumo de IA: ' . $exception->getMessage());

            return null;
        }
    }

    /**
     * Gasto estimado del mes en curso (de todo el sistema o de un usuario).
     */
    public function monthlySpendUsd(?int $userId = null): float
    {
        if (! Schema::hasTable('ai_usages')) {
            return 0.0;
        }

        $query = AiUsage::query()
            ->where('status', 'ok')
            ->where('created_at', '>=', now()->startOfMonth());

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return round((float) $query->sum('cost_usd'), 6);
    }

    /**
     * Consultas registradas hoy por un usuario.
     */
    public function todayCount(int $userId): int
    {
        if (! Schema::hasTable('ai_usages')) {
            return 0;
        }

        return AiUsage::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }

    /**
     * El texto de la consulta solo se guarda si el parámetro IA-00009 lo
     * permite. Por defecto no se almacena.
     */
    private function excerpt(?string $prompt): ?string
    {
        if ($prompt === null || trim($prompt) === '' || ! IaSettings::storesPrompts()) {
            return null;
        }

        return Str::limit($prompt, 1000);
    }
}
