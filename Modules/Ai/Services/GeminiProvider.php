<?php

namespace Modules\Ai\Services;

use Illuminate\Support\Facades\Http;
use Modules\Ai\Support\AiPricing;
use Modules\Ai\Support\AiResult;
use Modules\Ai\Support\IaSettings;
use RuntimeException;

/**
 * Proveedor de Gemini (generateContent).
 *
 * Se llama desde PHP y no desde el navegador para que el consumo de tokens y
 * el costo queden registrados en el sistema.
 */
class GeminiProvider implements AiProvider
{
    public function name(): string
    {
        return 'gemini';
    }

    public function model(): string
    {
        return IaSettings::geminiModel();
    }

    public function chat(string $message, array $options = []): AiResult
    {
        $message = trim($message);

        if ($message === '') {
            throw new RuntimeException('El mensaje para la IA no puede estar vacío.');
        }

        $model = trim((string) ($options['model'] ?? $this->model()));
        $model = $model !== '' ? $model : $this->model();

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $message],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => (float) ($options['temperature'] ?? IaSettings::temperature()),
                'maxOutputTokens' => (int) ($options['max_output_tokens'] ?? IaSettings::maxOutputTokens()),
            ],
        ];

        $instructions = $options['instructions'] ?? null;

        if (is_string($instructions) && trim($instructions) !== '') {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => trim($instructions)],
                ],
            ];
        }

        $baseUrl = rtrim((string) config('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');

        $response = Http::withQueryParameters(['key' => $this->apiKey()])
            ->timeout((int) config('ai.gemini.timeout', 60))
            ->acceptJson()
            ->post($baseUrl . '/models/' . rawurlencode($model) . ':generateContent', $payload);

        if (! $response->successful()) {
            $detail = $response->json('error.message') ?: 'sin detalle';

            throw new RuntimeException('Gemini respondió un error (' . $response->status() . '): ' . $detail);
        }

        $data = $response->json();
        $usage = is_array($data['usageMetadata'] ?? null) ? $data['usageMetadata'] : [];

        $promptTokens = max(0, (int) ($usage['promptTokenCount'] ?? 0));
        $completionTokens = max(0, (int) ($usage['candidatesTokenCount'] ?? 0));
        $totalTokens = max(0, (int) ($usage['totalTokenCount'] ?? ($promptTokens + $completionTokens)));

        return new AiResult(
            'gemini',
            $model,
            $this->text($data),
            $promptTokens,
            $completionTokens,
            $totalTokens,
            AiPricing::cost('gemini', $model, $promptTokens, $completionTokens)
        );
    }

    private function apiKey(): string
    {
        $key = IaSettings::geminiApiKey();

        if ($key === null) {
            throw new RuntimeException('Falta configurar la API Key de Gemini en el parámetro IA-00012 del sistema.');
        }

        return $key;
    }

    private function text(array $data): string
    {
        $parts = $data['candidates'][0]['content']['parts'] ?? [];
        $texts = [];

        foreach ($parts as $part) {
            if (isset($part['text']) && is_string($part['text'])) {
                $texts[] = $part['text'];
            }
        }

        $text = trim(implode('', $texts));

        if ($text === '') {
            throw new RuntimeException('Gemini no devolvió una respuesta de texto.');
        }

        return $text;
    }
}
