<?php

namespace Modules\Academic\Services;

use App\Models\Parameter;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OpenAiAssistantService
{
    private const BASE_URL = 'https://api.openai.com/v1';

    public function sendPrompt(int|string $userId, string $message, ?string $fileName = null): string
    {
        if (trim($message) === '') {
            throw new RuntimeException('El mensaje para OpenAI no puede estar vacio.');
        }

        $filePath = null;
        $input = [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'input_text',
                        'text' => $message,
                    ],
                ],
            ],
        ];

        if ($fileName) {
            Cache::forget($this->responseCacheKey($userId));

            $filePath = $this->resolveFilePath($fileName);
            $input[0]['content'][] = [
                'type' => 'input_file',
                'file_id' => $this->uploadFile($filePath, 'user_data'),
            ];
        }

        try {
            $response = $this->requestResponses($userId, $input);
        } catch (RequestException $exception) {
            // Si la conversacion previa expiro en OpenAI, reintentar una vez sin historial.
            $payload = $exception->response->json() ?? [];

            if ($this->isPreviousResponseError($payload) && Cache::pull($this->responseCacheKey($userId))) {
                $response = $this->requestResponses($userId, $input);
            } else {
                throw new RuntimeException($this->openAiErrorMessage($exception), 0, $exception);
            }
        } finally {
            $this->deleteTemporaryFile($filePath);
        }

        if (!empty($response['id'])) {
            Cache::put($this->responseCacheKey($userId), $response['id'], now()->addHours(12));
        }

        return $this->responseText($response);
    }

    public function censorText(int|string $userId, string $text): string
    {
        $prompt = 'por favor censura con asteriscos los nombres personales y de empresas privadas en el siguiente texto, las publicas no; solo responde lo que pedi sin palabras previas o saludos: ';

        return $this->sendPrompt($userId, $prompt . $text);
    }

    private function requestResponses(int|string $userId, array $input): array
    {
        $payload = [
            'model' => $this->model(),
            'input' => $input,
        ];

        $instructions = config('academic.openai.instructions');

        if ($instructions) {
            $payload['instructions'] = $instructions;
        }

        $previousResponseId = Cache::get($this->responseCacheKey($userId));

        if ($previousResponseId) {
            $payload['previous_response_id'] = $previousResponseId;
        }

        return $this->client()
            ->post('/responses', $payload)
            ->throw()
            ->json();
    }

    private function isPreviousResponseError(array $errorPayload): bool
    {
        $message = Str::lower($errorPayload['error']['message'] ?? '');

        return $message !== '' && Str::contains($message, 'previous response');
    }

    private function openAiErrorMessage(RequestException $exception): string
    {
        $errorPayload = $exception->response->json() ?? [];
        $apiMessage = $errorPayload['error']['message'] ?? '';

        if ($apiMessage !== '') {
            return 'OpenAI respondio un error (' . $exception->response->status() . '): ' . $apiMessage;
        }

        return 'Error al comunicarse con OpenAI (' . $exception->response->status() . '). Verifica la API Key del parametro P000025.';
    }

    private function uploadFile(string $filePath, string $purpose): string
    {
        return $this->client()
            ->attach('file', fopen($filePath, 'r'), basename($filePath))
            ->post('/files', [
                'purpose' => $purpose,
            ])
            ->throw()
            ->json('id');
    }

    private function resolveFilePath(string $fileName): string
    {
        $allowedExtensions = ['txt', 'pdf', 'docx', 'doc', 'xls', 'xlsx'];
        $extension = Str::lower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('Tipo de archivo no permitido para OpenAI.');
        }

        $basePath = base_path('asistente_lyon');
        $filePath = $basePath . DIRECTORY_SEPARATOR . ltrim($fileName, DIRECTORY_SEPARATOR);
        $realBasePath = realpath($basePath);
        $realFilePath = realpath($filePath);

        if (!$realBasePath || !$realFilePath || !Str::startsWith($realFilePath, $realBasePath)) {
            throw new RuntimeException('Archivo no encontrado o ruta invalida.');
        }

        return $realFilePath;
    }

    private function deleteTemporaryFile(?string $filePath): void
    {
        if ($filePath && is_file($filePath)) {
            @unlink($filePath);
        }
    }

    private function client(): PendingRequest
    {
        return Http::withToken($this->apiKey())
            ->baseUrl(self::BASE_URL)
            ->timeout((int) config('academic.openai.timeout', 60))
            ->retry(2, 300, function ($exception) {
                return $exception instanceof RequestException && $exception->response?->serverError();
            });
    }

    private function apiKey(): string
    {
        $apiKey = $this->apiKeyFromParameter();

        if (!$apiKey) {
            $apiKey = config('academic.openai.api_key');
        }

        if (!$apiKey) {
            throw new RuntimeException('Falta configurar la API Key de OpenAI en el parametro P000025 del sistema.');
        }

        return $this->sanitizeApiKey($apiKey);
    }

    /**
     * Limpia la key de caracteres invisibles o espacios que suelen quedar
     * al copiarla/pegarla (espacios, saltos de linea, zero-width, NBSP).
     */
    private function sanitizeApiKey(string $apiKey): string
    {
        $clean = preg_replace('/[\s\x{200B}-\x{200D}\x{FEFF}\x{00A0}]+/u', '', $apiKey) ?? '';

        return trim($clean);
    }

    protected function apiKeyFromParameter(): ?string
    {
        $parameterCode = config('academic.openai.api_key_parameter');

        if (!$parameterCode) {
            return null;
        }

        $value = Cache::remember(
            'academic:openai-api-key:' . $parameterCode,
            now()->addMinutes(60),
            function () use ($parameterCode) {
                return Parameter::where('parameter_code', $parameterCode)->value('value_default');
            }
        );

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function responseText(array $response): string
    {
        if (!empty($response['output_text'])) {
            return $response['output_text'];
        }

        foreach ($response['output'] ?? [] as $output) {
            foreach ($output['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text') {
                    return $content['text'] ?? '';
                }
            }
        }

        throw new RuntimeException('OpenAI no devolvio una respuesta de texto.');
    }

    private function model(): string
    {
        $model = config('academic.openai.model');

        if (!$model) {
            throw new RuntimeException('Falta configurar OPENAI_MODEL para usar OpenAI.');
        }

        return $model;
    }

    private function responseCacheKey(int|string $userId): string
    {
        return 'academic:openai-response:' . $userId;
    }
}
