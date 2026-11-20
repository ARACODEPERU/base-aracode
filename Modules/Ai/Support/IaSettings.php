<?php

namespace Modules\Ai\Support;

use App\Models\Parameter;
use Illuminate\Support\Facades\Cache;

/**
 * Lectura única de los ajustes de IA de la tabla `parameters`.
 *
 * La pantalla de Parámetros del sistema y el módulo de IA comparten estas
 * mismas filas: aquí solo se leen, nunca se duplican valores en otra tabla ni
 * en archivos de configuración. La caché usa la misma clave que ya usaba el
 * servicio de OpenAI ('academic:parameter:...') para que un cambio guardado
 * desde Parámetros se vea de inmediato en ambos lados.
 */
class IaSettings
{
    public const OPENAI_API_KEY = 'IA-00001';

    public const PROVIDER = 'IA-00002';

    public const OPENAI_AGENT_ID = 'IA-00003';

    public const AGENT_TIMEOUT = 'IA-00004';

    public const OPENAI_MODEL = 'IA-00005';

    public const GEMINI_MODEL = 'IA-00006';

    public const MONTHLY_BUDGET = 'IA-00007';

    public const DAILY_LIMIT = 'IA-00008';

    public const STORE_PROMPTS = 'IA-00009';

    public const MAX_OUTPUT_TOKENS = 'IA-00010';

    public const TEMPERATURE = 'IA-00011';

    public const GEMINI_API_KEY = 'IA-00012';

    public const BUDGET_WARNING_PERCENT = 'IA-00013';

    public const BUDGET_HARD_STOP = 'IA-00014';

    /**
     * Minutos que se conserva un parámetro en caché. El observador de
     * parámetros la invalida al guardar, así que este tiempo solo evita ir a
     * la base en cada lectura.
     */
    private const CACHE_MINUTES = 60;

    private const CACHE_PREFIX = 'academic:parameter:';

    /**
     * @return array<int, string>
     */
    public static function codes(): array
    {
        return [
            self::OPENAI_API_KEY,
            self::PROVIDER,
            self::OPENAI_AGENT_ID,
            self::AGENT_TIMEOUT,
            self::OPENAI_MODEL,
            self::GEMINI_MODEL,
            self::MONTHLY_BUDGET,
            self::DAILY_LIMIT,
            self::STORE_PROMPTS,
            self::MAX_OUTPUT_TOKENS,
            self::TEMPERATURE,
            self::GEMINI_API_KEY,
            self::BUDGET_WARNING_PERCENT,
            self::BUDGET_HARD_STOP,
        ];
    }

    /**
     * Código configurado para una clave del arreglo 'ai.parameters'.
     */
    public static function code(string $key): string
    {
        return (string) config('ai.parameters.' . $key, '');
    }

    /**
     * Valor crudo del parámetro. Devuelve null cuando la fila no existe.
     */
    public static function get(string $code, ?string $default = null): ?string
    {
        if ($code === '') {
            return $default;
        }

        $value = Cache::remember(
            self::CACHE_PREFIX . $code,
            now()->addMinutes(self::CACHE_MINUTES),
            fn () => Parameter::where('parameter_code', $code)->value('value_default')
        );

        return $value !== null ? (string) $value : $default;
    }

    public static function bool(string $code, bool $default = false): bool
    {
        $value = trim((string) self::get($code, ''));

        if ($value === '') {
            return $default;
        }

        return in_array(mb_strtolower($value), ['1', 'true', 'si', 'sí', 'on'], true);
    }

    public static function int(string $code, int $default = 0): int
    {
        $value = trim((string) self::get($code, ''));

        return $value !== '' && is_numeric($value) ? (int) $value : $default;
    }

    public static function float(string $code, float $default = 0.0): float
    {
        $value = trim((string) self::get($code, ''));

        return $value !== '' && is_numeric($value) ? (float) $value : $default;
    }

    /**
     * Proveedor activo: 'openai' o 'gemini'. El parámetro usa 1 y 2.
     */
    public static function provider(): string
    {
        $value = trim((string) self::get(self::PROVIDER, ''));

        return match ($value) {
            '1' => 'openai',
            '2' => 'gemini',
            default => (string) config('ai.defaults.provider', 'gemini'),
        };
    }

    public static function openAiModel(): string
    {
        $value = trim((string) self::get(self::OPENAI_MODEL, ''));

        return $value !== '' ? $value : (string) config('ai.defaults.openai_model', 'gpt-4.1-mini');
    }

    public static function geminiModel(): string
    {
        $value = trim((string) self::get(self::GEMINI_MODEL, ''));

        return $value !== '' ? $value : (string) config('ai.defaults.gemini_model', 'gemini-2.5-flash');
    }

    public static function openAiApiKey(): ?string
    {
        $value = self::cleanKey((string) self::get(self::OPENAI_API_KEY, ''));

        if ($value !== '') {
            return $value;
        }

        $fallback = self::cleanKey((string) config('academic.openai.api_key', ''));

        return $fallback !== '' ? $fallback : null;
    }

    public static function geminiApiKey(): ?string
    {
        $value = self::cleanKey((string) self::get(self::GEMINI_API_KEY, ''));

        if ($value !== '') {
            return $value;
        }

        $fallback = self::cleanKey((string) (env('GEMINI_API_KEY') ?: env('API_KEY_GEMINI')));

        return $fallback !== '' ? $fallback : null;
    }

    public static function maxOutputTokens(): int
    {
        return max(1, self::int(self::MAX_OUTPUT_TOKENS, (int) config('ai.defaults.max_output_tokens', 800)));
    }

    public static function temperature(): float
    {
        return min(2.0, max(0.0, self::float(self::TEMPERATURE, (float) config('ai.defaults.temperature', 0.2))));
    }

    public static function monthlyBudgetUsd(): float
    {
        return max(0.0, self::float(self::MONTHLY_BUDGET, (float) config('ai.defaults.monthly_budget_usd', 0)));
    }

    public static function dailyLimit(): int
    {
        return max(0, self::int(self::DAILY_LIMIT, (int) config('ai.defaults.daily_limit', 0)));
    }

    public static function storesPrompts(): bool
    {
        return self::bool(self::STORE_PROMPTS, (bool) config('ai.defaults.store_prompts', false));
    }

    /**
     * Porcentaje del presupuesto en el que se emite el aviso previo.
     * 0 desactiva el aviso (el corte al 100% sigue existiendo).
     */
    public static function budgetWarningPercent(): int
    {
        return min(100, max(0, self::int(self::BUDGET_WARNING_PERCENT, (int) config('ai.defaults.budget_warning_percent', 80))));
    }

    /**
     * true si las consultas se cortan al superar el presupuesto mensual.
     */
    public static function budgetHardStop(): bool
    {
        return self::bool(self::BUDGET_HARD_STOP, (bool) config('ai.defaults.budget_hard_stop', true));
    }

    /**
     * Invalida la caché de un parámetro (o de todos si no se indica código).
     */
    public static function flush(?string $code = null): void
    {
        if ($code === null || $code === '') {
            self::flushAll();

            return;
        }

        Cache::forget(self::CACHE_PREFIX . $code);
        // Clave anterior del servicio de OpenAI: se limpia para no dejar un
        // valor viejo activo en instalaciones que vienen de esa versión.
        Cache::forget('academic:openai-api-key:' . $code);
    }

    public static function flushAll(): void
    {
        foreach (self::codes() as $code) {
            self::flush($code);
        }
    }

    /**
     * Quita espacios, saltos de línea y caracteres invisibles que suelen
     * quedar al copiar y pegar una clave.
     */
    private static function cleanKey(string $key): string
    {
        $clean = preg_replace('/[\s\x{200B}-\x{200D}\x{FEFF}\x{00A0}]+/u', '', $key) ?? '';

        return trim($clean);
    }
}
