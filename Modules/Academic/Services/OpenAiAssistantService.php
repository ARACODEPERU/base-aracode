<?php

namespace Modules\Academic\Services;

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

        if ($fileName === 'forget') {
            Cache::forget($this->cacheKey($userId));
            Cache::forget($this->responseCacheKey($userId));
            return 'Conversacion olvidada correctamente';
        }

        if (!$this->assistantId()) {
            return $this->sendPromptWithResponses($userId, $message, $fileName);
        }

        $threadId = $this->threadId($userId, $fileName);
        $attachments = [];
        $filePath = null;

        if ($fileName) {
            $filePath = $this->resolveFilePath($fileName);
            $attachments[] = [
                'file_id' => $this->uploadFile($filePath, 'assistants'),
                'tools' => [
                    ['type' => 'file_search'],
                ],
            ];
        }

        $payload = [
            'role' => 'user',
            'content' => $message,
        ];

        if ($attachments) {
            $payload['attachments'] = $attachments;
        }

        $this->client()->post("/threads/{$threadId}/messages", $payload)->throw();

        $run = $this->client()
            ->post("/threads/{$threadId}/runs", [
                'assistant_id' => $this->assistantId(),
            ])
            ->throw()
            ->json();

        $run = $this->waitForRun($threadId, $run['id']);
        $this->deleteTemporaryFile($filePath);

        if (($run['status'] ?? null) !== 'completed') {
            throw new RuntimeException('OpenAI no completo la respuesta. Estado: ' . ($run['status'] ?? 'desconocido'));
        }

        return $this->latestAssistantResponse($threadId);
    }

    private function sendPromptWithResponses(int|string $userId, string $message, ?string $fileName = null): string
    {
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

        $response = $this->client()
            ->post('/responses', $payload)
            ->throw()
            ->json();

        $this->deleteTemporaryFile($filePath);

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

    private function threadId(int|string $userId, ?string $fileName = null): string
    {
        $cacheKey = $this->cacheKey($userId);

        if ($fileName) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addHours(12), function () {
            return $this->client()->post('/threads')->throw()->json('id');
        });
    }

    private function waitForRun(string $threadId, string $runId): array
    {
        $maxAttempts = (int) config('academic.openai.max_run_attempts', 120);
        $delayMilliseconds = (int) config('academic.openai.run_poll_delay_ms', 500);

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $run = $this->client()
                ->get("/threads/{$threadId}/runs/{$runId}")
                ->throw()
                ->json();

            if (in_array($run['status'] ?? null, ['completed', 'failed', 'cancelled', 'expired'], true)) {
                return $run;
            }

            usleep($delayMilliseconds * 1000);
        }

        throw new RuntimeException('OpenAI excedio el tiempo de espera para responder.');
    }

    private function latestAssistantResponse(string $threadId): string
    {
        $messages = $this->client()
            ->get("/threads/{$threadId}/messages", [
                'limit' => 10,
                'order' => 'desc',
            ])
            ->throw()
            ->json('data', []);

        foreach ($messages as $message) {
            if (($message['role'] ?? null) !== 'assistant') {
                continue;
            }

            foreach ($message['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'text') {
                    return $content['text']['value'] ?? '';
                }
            }
        }

        throw new RuntimeException('OpenAI no devolvio una respuesta de texto.');
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
            ->withHeaders([
                'OpenAI-Beta' => 'assistants=v2',
            ])
            ->baseUrl(self::BASE_URL)
            ->timeout((int) config('academic.openai.timeout', 60))
            ->retry(2, 300, function ($exception) {
                return $exception instanceof RequestException && $exception->response?->serverError();
            });
    }

    private function apiKey(): string
    {
        $apiKey = config('academic.openai.api_key');

        if (!$apiKey) {
            throw new RuntimeException('Falta configurar API_KEY_IA u OPENAI_API_KEY.');
        }

        return $apiKey;
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

    private function assistantId(): ?string
    {
        $assistantId = config('academic.openai.assistant_id');

        return $assistantId ?: null;
    }

    private function model(): string
    {
        $model = config('academic.openai.model');

        if (!$model) {
            throw new RuntimeException('Falta configurar OPENAI_MODEL para usar OpenAI sin assistant_id.');
        }

        return $model;
    }

    private function cacheKey(int|string $userId): string
    {
        return 'academic:openai-assistant-thread:' . $userId;
    }

    private function responseCacheKey(int|string $userId): string
    {
        return 'academic:openai-response:' . $userId;
    }
}
