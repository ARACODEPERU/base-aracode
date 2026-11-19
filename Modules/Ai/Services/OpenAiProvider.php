<?php

namespace Modules\Ai\Services;

use Illuminate\Support\Facades\Http;
use Modules\Ai\Support\AiPricing;
use Modules\Ai\Support\AiResult;
use Modules\Ai\Support\IaSettings;
use RuntimeException;

/**
 * Proveedor de OpenAI (Responses API).
 *
 * Devuelve el texto junto con los tokens informados por OpenAI, para que el
 * consumo quede registrado y auditable.
 */
class OpenAiProvider implements AiProvider
{
    public function name(): string
    {
        return 'openai';
    }

    public function model(): string
    {
        return IaSettings::openAiModel();
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
            'model' => $model,
            'input' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'input_text', 'text' => $message],
                    ],
                ],
            ],
            'max_output_tokens' => (int) ($options['max_output_tokens'] ?? IaSettings::maxOutputTokens()),
            'temperature' => (float) ($options['temperature'] ?? IaSettings::temperature()),
        ];

        $instructions = $options['instructions'] ?? config('ai.openai.instructions');

        if (is_string($instructions) && trim($instructions) !== '') {
            $payload['instructions'] = trim($instructions);
        }

        if (! empty($options['previous_response_id'])) {
            $payload['previous_response_id'] = (string) $options['previous_response_id'];
        }

        $response = Http::withToken($this->apiKey())
            ->baseUrl((string) config('ai.openai.base_url', 'https://api.openai.com/v1'))
            ->timeout((int) config('ai.openai.timeout', 60))
            ->acceptJson()
            ->post('/responses', $payload);

        if (! $response->successful()) {
            $detail = $response->json('error.message') ?: 'sin detalle';

            throw new RuntimeException('OpenAI respondió un error (' . $response->status() . '): ' . $detail);
        }

        $data = $response->json();
        $usage = is_array($data['usage'] ?? null) ? $data['usage'] : [];

        $promptTokens = max(0, (int) ($usage['input_tokens'] ?? 0));
        $completionTokens = max(0, (int) ($usage['output_tokens'] ?? 0));
        $totalTokens = max(0, (int) ($usage['total_tokens'] ?? ($promptTokens + $completionTokens)));

        $usedModel = trim((string) ($data['model'] ?? $model));
        $usedModel = $usedModel !== '' ? $usedModel : $model;

        return new AiResult(
            'openai',
            $usedModel,
            $this->text($data),
            $promptTokens,
            $completionTokens,
            $totalTokens,
            AiPricing::cost('openai', $usedModel, $promptTokens, $completionTokens)
        );
    }

    private function apiKey(): string
    {
        $key = IaSettings::openAiApiKey();

        if ($key === null) {
            throw new RuntimeException('Falta configurar la API Key de OpenAI en el parámetro IA-00001 del sistema.');
        }

        return $key;
    }

    /**
     * Extrae el texto de la respuesta de la Responses API.
     */
    private function text(array $data): string
    {
        if (! empty($data['output_text']) && is_string($data['output_text'])) {
            return trim($data['output_text']);
        }

        $parts = [];

        foreach ($data['output'] ?? [] as $output) {
            foreach ($output['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text' && isset($content['text'])) {
                    $parts[] = (string) $content['text'];
                }
            }
        }

        $text = trim(implode('', $parts));

        if ($text === '') {
            throw new RuntimeException('OpenAI no devolvió una respuesta de texto.');
        }

        return $text;
    }
}
