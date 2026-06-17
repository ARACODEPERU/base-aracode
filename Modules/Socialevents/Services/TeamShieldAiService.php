<?php

namespace Modules\Socialevents\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TeamShieldAiService
{
    /**
     * Genera un escudo vía servicio Socket.IO o, como respaldo, Gemini.
     *
     * @return array{imageBase64: string, mimeType: string}
     */
    public function generate(string $prompt): array
    {
        $fromSocket = $this->trySocketServer($prompt);
        if ($fromSocket !== null) {
            return $fromSocket;
        }

        $fromGemini = $this->tryGemini($prompt);
        if ($fromGemini !== null) {
            return $fromGemini;
        }

        throw new RuntimeException(
            'No se pudo generar el escudo. Verifique que el servicio en VITE_SOCKET_IO_SERVER esté activo o configure GEMINI_API_KEY.'
        );
    }

    /**
     * @return array{imageBase64: string, mimeType: string}|null
     */
    private function trySocketServer(string $prompt): ?array
    {
        $baseUrl = rtrim((string) config('services.socket_io.internal_url', 'http://127.0.0.1:3000'), '/');
        $internalKey = (string) config('services.internal_api.key', '');

        try {
            $client = new Client([
                'verify' => app()->environment('production'),
                'timeout' => 120,
                'connect_timeout' => 10,
            ]);

            $headers = [];
            if ($internalKey !== '') {
                $headers['X-Internal-Api-Key'] = $internalKey;
            }

            $response = $client->post("{$baseUrl}/api/ai/generate-shield", [
                'headers' => $headers,
                'json' => [
                    'prompt' => $prompt,
                ],
            ]);

            $payload = json_decode((string) $response->getBody(), true);
            if (! is_array($payload)) {
                return null;
            }

            return $this->normalizePayload($payload);
        } catch (\Throwable $e) {
            Log::warning('TeamShieldAiService socket fallback: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @return array{imageBase64: string, mimeType: string}|null
     */
    private function tryGemini(string $prompt): ?array
    {
        $apiKey = env('GEMINI_API_KEY') ?: env('API_KEY_GEMINI');
        if (! $apiKey) {
            return null;
        }

        $model = env('GEMINI_IMAGE_MODEL', 'gemini-2.0-flash-preview-image-generation');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        try {
            $response = Http::timeout(120)
                ->withQueryParameters(['key' => $apiKey])
                ->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseModalities' => ['TEXT', 'IMAGE'],
                    ],
                ]);

            if (! $response->successful()) {
                $message = $response->json('error.message') ?? $response->body();
                throw new RuntimeException(is_string($message) ? $message : 'Error al generar imagen con Gemini.');
            }

            $parts = $response->json('candidates.0.content.parts') ?? [];
            foreach ($parts as $part) {
                $inline = $part['inlineData'] ?? $part['inline_data'] ?? null;
                if (! is_array($inline) || empty($inline['data'])) {
                    continue;
                }

                return [
                    'imageBase64' => (string) $inline['data'],
                    'mimeType' => (string) ($inline['mimeType'] ?? $inline['mime_type'] ?? 'image/png'),
                ];
            }

            throw new RuntimeException('Gemini no devolvió una imagen en la respuesta.');
        } catch (\Throwable $e) {
            Log::warning('TeamShieldAiService gemini: ' . $e->getMessage());
            throw new RuntimeException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{imageBase64: string, mimeType: string}|null
     */
    private function normalizePayload(array $payload): ?array
    {
        $raw = $payload['imageBase64']
            ?? $payload['image']
            ?? $payload['base64']
            ?? null;

        if (! is_string($raw) || trim($raw) === '') {
            if (isset($payload['error']['message'])) {
                throw new RuntimeException((string) $payload['error']['message']);
            }

            return null;
        }

        if (str_starts_with($raw, 'data:')) {
            if (preg_match('#^data:(image/[^;]+);base64,(.+)$#', $raw, $matches)) {
                return [
                    'imageBase64' => $matches[2],
                    'mimeType' => $matches[1],
                ];
            }
        }

        return [
            'imageBase64' => $raw,
            'mimeType' => (string) ($payload['mimeType'] ?? 'image/png'),
        ];
    }
}
