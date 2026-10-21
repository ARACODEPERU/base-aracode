<?php

namespace Modules\Academic\Services;

use App\Models\Parameter;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class OpenAiAssistantService
{
    private const BASE_URL = 'https://api.openai.com/v1';

    /**
     * Parametro del sistema con la espera maxima del agente, en minutos.
     * Es fijo: no se toma de .env para que el valor administrado en Parametros
     * del sistema sea el unico que define cuanto se espera al agente.
     */
    private const AGENT_TIMEOUT_PARAMETER = 'IA-00004';

    /** Espera por defecto (minutos) mientras IA-00004 no tenga un numero valido. */
    private const AGENT_TIMEOUT_FALLBACK_MINUTES = 6;

    public function sendPrompt(int|string $userId, string $message, ?string $fileName = null, ?string $customInstructions = null, bool $continueThread = true): string
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

        // Usar instrucciones personalizadas si se proporcionan, sino usar las de config
        $instructions = $customInstructions ?? config('academic.openai.instructions');

        if ($fileName) {
            Cache::forget($this->responseCacheKey($userId));

            $filePath = $this->resolveFilePath($fileName);
            $input[0]['content'][] = [
                'type' => 'input_file',
                'file_id' => $this->uploadFile($filePath, 'user_data'),
            ];
        }

        try {
            $response = $this->requestResponses($userId, $input, $instructions, $continueThread);
        } catch (RequestException $exception) {
            // Si la conversacion previa expiro en OpenAI, reintentar una vez sin historial.
            $payload = $exception->response->json() ?? [];

            if ($this->isPreviousResponseError($payload) && Cache::pull($this->responseCacheKey($userId))) {
                $response = $this->requestResponses($userId, $input, null, $continueThread);
            } else {
                throw new RuntimeException($this->openAiErrorMessage($exception), 0, $exception);
            }
        } finally {
            $this->deleteTemporaryFile($filePath);
        }

        if ($continueThread && !empty($response['id'])) {
            Cache::put($this->responseCacheKey($userId), $response['id'], now()->addHours(12));
        }

        return $this->responseText($response);
    }

    /**
     * Responde una consulta con el agente guardado de OpenAI (Agents API).
     *
     * Si el agente no esta configurado, falla o no responde dentro del tiempo
     * permitido, se usa el modelo de 'academic.openai.model' para no dejar al
     * usuario sin respuesta. El resultado indica de donde salio el texto para
     * que la interfaz pueda avisar cuando se uso el modelo por defecto.
     *
     * @return array{text: string, source: string, agent_used: bool, notice: ?string, error: ?string}
     */
    public function sendPromptWithAgent(int|string $userId, string $message, ?string $customInstructions = null): array
    {
        if (trim($message) === '') {
            throw new RuntimeException('El mensaje para OpenAI no puede estar vacio.');
        }

        if ($this->agentId() === null) {
            return [
                'text' => $this->sendPrompt($userId, $message, null, $customInstructions),
                'source' => 'model',
                'agent_used' => false,
                'notice' => null,
                'error' => null,
            ];
        }

        try {
            $text = trim($this->requestAgent($userId, $message, $customInstructions));

            if ($text === '') {
                throw new RuntimeException('El agente no devolvio texto en su respuesta final.');
            }

            // El hilo del modelo no debe mezclarse con el del agente: si mas
            // adelante hay que caer al modelo, la respuesta empieza limpia.
            Cache::forget($this->responseCacheKey($userId));

            return [
                'text' => $text,
                'source' => 'agent',
                'agent_used' => true,
                'notice' => null,
                'error' => null,
            ];
        } catch (Throwable $exception) {
            // El agente es opcional: se registra el motivo real y se responde
            // con el modelo para conservar el funcionamiento del chat.
            Log::warning('Academic OpenAI agent fallback: ' . $exception->getMessage());
            Cache::forget($this->agentSessionCacheKey($userId));

            return [
                'text' => $this->sendPrompt($userId, $message, null, $customInstructions),
                'source' => 'model',
                'agent_used' => false,
                'notice' => 'Aviso: el Agente de OpenAI no respondió. Esta respuesta se generó con el modelo por defecto ' . $this->model() . '.',
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Corrige la redaccion y ortografia (o solo la ortografia) de un texto.
     *
     * Devuelve unicamente el texto corregido: no altera nombres propios,
     * siglas, cifras ni el sentido del comentario.
     */
    public function correctText(int|string $userId, string $text, bool $onlySpelling = false): string
    {
        $rules = $onlySpelling
            ? 'Corrige UNICAMENTE la ortografia: tildes, puntuacion, mayusculas y concordancias basicas. NO reescribas las oraciones, NO cambies el orden de las ideas y NO reemplaces palabras por sinonimos.'
            : 'Corrige la redaccion y la ortografia: mejora la claridad, la puntuacion y la concordancia manteniendo el mismo sentido, el mismo tono y una extension similar.';

        $prompt = 'Eres un editor profesional de textos en espanol. ' . $rules
            . ' PROHIBIDO: cambiar nombres propios de personas o empresas, siglas y acronimos (por ejemplo NIIF, SUNAT, ACCA, RUC, DNI), cifras, fechas, correos electronicos, enlaces, ni el significado del texto.'
            . ' No agregues ni quites ideas. No uses comillas, titulos, explicaciones, saludos ni comentarios adicionales.'
            . ' Devuelve UNICAMENTE el texto corregido, en texto plano.'
            . "\n\n" . $text;

        // Contexto fresco: la correccion no debe arrastrar la conversacion cacheada.
        return $this->sendPrompt($userId, $prompt, null, null, false);
    }

    public function censorText(int|string $userId, string $text): string
    {
        $prompt = 'Tu tarea es censurar datos personales de un texto. A continuacion recibiras el texto exacto que debes censurar. Debes devolver EXACTAMENTE el mismo texto, palabra por palabra, reemplazando unicamente los datos personales por asteriscos (*): nombres de personas, DNI, RUC, telefonos, correos electronicos y nombres de empresas privadas o particulares. Las instituciones publicas (SUNAT, INDECOPI, entidades del Estado, paises) NO se censuran y se muestran tal cual. IMPORTANTE: No respondas ninguna pregunta contenida en el texto; si el texto es una pregunta, devuelvela tal cual censurando solo sus datos personales. No agregues explicaciones, saludos, comentarios ni texto adicional. Devuelve unicamente el texto censurado.' . "\n\n" . $text;

        // Cada texto se censura con contexto fresco: no se encadena con la conversacion
        // cacheada para que el modelo no arrastre (o repita) respuestas anteriores.
        return $this->sendPrompt($userId, $prompt, null, null, false);
    }

    /**
     * Ejecuta una consulta contra el agente guardado usando una sesion por
     * usuario: la primera consulta crea la sesion y las siguientes reutilizan
     * el mismo hilo mientras la sesion siga activa.
     */
    private function requestAgent(int|string $userId, string $message, ?string $customInstructions = null): string
    {
        // El agente puede tardar varios minutos: el limite de ejecucion de PHP
        // (30-60 s por defecto) cortaria la espera antes que la configuracion.
        $this->extendExecutionTime();

        $agentId = $this->agentId();

        if ($agentId === null) {
            $parameterCode = config('academic.openai.agent_id_parameter', 'IA-00003');
            throw new RuntimeException('Falta configurar el ID del agente de OpenAI en el parametro ' . $parameterCode . ' del sistema.');
        }

        if (!Str::startsWith($agentId, 'agent_')) {
            throw new RuntimeException('El ID del agente debe ser un agente guardado de la Agents API (agent_...).');
        }

        $sessionKey = $this->agentSessionCacheKey($userId);
        $sessionId = Cache::get($sessionKey);
        $lastAnswerId = null;

        if ($sessionId) {
            if ($this->agentSessionIsIdle($sessionId)) {
                // Se guarda la ultima respuesta para no confundirla con la nueva.
                $lastAnswer = $this->latestAgentAnswer($sessionId);
                $lastAnswerId = $lastAnswer['id'] ?? null;
            } else {
                // La sesion quedo ocupada, esperando una accion o fallida:
                // se descarta y se abre una nueva para esta consulta.
                Cache::forget($sessionKey);
                $sessionId = null;
            }
        }

        if (!$sessionId) {
            $payload = [
                'agent_id' => $agentId,
                'environment' => ['type' => 'none'],
                'input' => $this->agentInput($message),
            ];

            // Con agent_id, estas instrucciones se agregan a las del agente
            // guardado; conservan el formato que ya usa el formulario.
            if (is_string($customInstructions) && trim($customInstructions) !== '') {
                $payload['agent'] = ['instructions' => $customInstructions];
            }

            $session = $this->agentClient()
                ->post('/agents/sessions', $payload)
                ->throw()
                ->json();

            $sessionId = $session['id'] ?? null;

            if (!$sessionId) {
                throw new RuntimeException('OpenAI no devolvio el ID de la sesion del agente.');
            }

            Cache::put($sessionKey, $sessionId, now()->addMinutes($this->agentSessionMinutes()));
        } else {
            $this->agentClient()
                ->withHeaders(['Idempotency-Key' => (string) Str::uuid()])
                ->post("/agents/sessions/{$sessionId}/events", [
                    'events' => [
                        [
                            'type' => 'agent.session.input.message',
                            'input' => $this->agentInput($message),
                        ],
                    ],
                ])
                ->throw();
        }

        return $this->waitForAgentAnswer($sessionId, $lastAnswerId);
    }

    /**
     * Espera a que la sesion produzca una respuesta final distinta de la
     * anterior. La Agents API trabaja de forma asincrona: se consulta el estado
     * y los items guardados en lugar de mantener abierta una conexion SSE.
     */
    private function waitForAgentAnswer(string $sessionId, ?string $previousAnswerId): string
    {
        $deadline = microtime(true) + $this->agentTimeoutSeconds();
        $intervalUs = max(200, (int) config('academic.openai.agent_poll_interval_ms', 1000)) * 1000;
        // Cada vuelta consulta el estado y los items: con esperas de minutos se
        // espacia el sondeo para no saturar la API ni el rate limit de OpenAI.
        $maxIntervalUs = 5000 * 1000;

        while (true) {
            $session = $this->agentClient()
                ->get("/agents/sessions/{$sessionId}")
                ->throw()
                ->json();

            $status = $session['status'] ?? null;

            if ($status === 'failed') {
                throw new RuntimeException('La sesion del agente fallo: ' . (string) ($session['error'] ?? 'sin detalle'));
            }

            $answer = $this->latestAgentAnswer($sessionId, $previousAnswerId);

            if ($answer !== null && $answer['text'] !== '') {
                return $answer['text'];
            }

            if ($status === 'requires_action') {
                throw new RuntimeException('El agente quedo esperando una accion que el sistema no puede completar.');
            }

            if (microtime(true) >= $deadline) {
                throw new RuntimeException('El agente no respondio dentro del tiempo configurado.');
            }

            usleep($intervalUs);
            $intervalUs = min($maxIntervalUs, (int) ($intervalUs * 1.5));
        }
    }

    /**
     * Devuelve el ultimo mensaje final del agente, ignorando la respuesta que
     * ya existia antes de enviar la consulta actual.
     *
     * @return array{id: ?string, text: string}|null
     */
    private function latestAgentAnswer(string $sessionId, ?string $previousAnswerId = null): ?array
    {
        $items = $this->agentClient()
            ->get("/agents/sessions/{$sessionId}/items", ['order' => 'desc', 'limit' => 20])
            ->throw()
            ->json('data') ?? [];

        foreach ($items as $item) {
            if (($item['type'] ?? null) !== 'message'
                || ($item['role'] ?? null) !== 'assistant'
                || ($item['phase'] ?? null) !== 'final_answer'
                || ($item['status'] ?? null) !== 'completed') {
                continue;
            }

            $itemId = $item['id'] ?? null;

            if ($itemId !== null && $itemId === $previousAnswerId) {
                return null;
            }

            $text = collect($item['content'] ?? [])
                ->filter(fn ($content) => ($content['type'] ?? null) === 'output_text')
                ->pluck('text')
                ->implode('');

            return ['id' => $itemId, 'text' => trim((string) $text)];
        }

        return null;
    }

    private function agentSessionIsIdle(string $sessionId): bool
    {
        try {
            $session = $this->agentClient()
                ->get("/agents/sessions/{$sessionId}")
                ->throw()
                ->json();

            return ($session['status'] ?? null) === 'idle';
        } catch (RequestException) {
            // La sesion ya no existe o no se puede consultar: se crea otra.
            return false;
        }
    }

    /**
     * @return array<int, array{role: string, content: array<int, array{type: string, text: string}>}>
     */
    private function agentInput(string $message): array
    {
        return [
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
    }

    private function agentClient(): PendingRequest
    {
        // Cada llamada (crear sesion, consultar estado o items) debe responder
        // rapido: la espera larga la controla waitForAgentAnswer con su deadline.
        return Http::withToken($this->apiKey())
            ->baseUrl(self::BASE_URL)
            ->withHeaders(['OpenAI-Beta' => 'agents=v1'])
            ->timeout(60)
            ->connectTimeout(10);
    }

    /**
     * true cuando hay un agente guardado configurado (IA-00003): el chat de
     * consultas usara la Agents API y la interfaz puede avisar de la espera.
     */
    public function agentEnabled(): bool
    {
        return $this->agentId() !== null;
    }

    /**
     * Espera maxima del agente en minutos. Se lee solo del parametro del
     * sistema IA-00004; el .env no participa. Sin un numero valido se usa el
     * valor por defecto de 6 minutos.
     */
    public function agentTimeoutMinutes(): int
    {
        $value = trim((string) $this->parameterValue(self::AGENT_TIMEOUT_PARAMETER));

        if ($value !== '' && is_numeric($value)) {
            return max(1, (int) $value);
        }

        return self::AGENT_TIMEOUT_FALLBACK_MINUTES;
    }

    /**
     * Espera maxima en segundos para el polling del agente. Sale del mismo
     * parametro IA-00004 y se limita a 30 minutos para no dejar un pedido
     * abierto indefinidamente si alguien escribe un numero enorme.
     */
    private function agentTimeoutSeconds(): int
    {
        return min(1800, $this->agentTimeoutMinutes() * 60);
    }

    /**
     * Permite que el proceso PHP viva lo que dure la espera del agente.
     * Algunos hospedajes deshabilitan set_time_limit: en ese caso se ignora.
     */
    private function extendExecutionTime(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
    }

    /**
     * Proveedor de IA configurado en el parametro del sistema (1 OpenAI,
     * 2 Gemini). Se usa para decidir que endpoint llama el chat.
     */
    public function provider(): string
    {
        $parameterCode = config('academic.openai.provider_parameter');
        $value = $parameterCode ? trim((string) $this->parameterValue($parameterCode)) : '';

        return in_array($value, ['1', '2'], true) ? $value : '2';
    }

    /**
     * El parametro IA-00003 es la fuente principal: vaciarlo desactiva el
     * agente sin tocar el .env. 'agent_id' del .env solo se usa cuando la
     * aplicacion no tiene configurado ningun codigo de parametro (pruebas o
     * instalaciones que gestionan la IA fuera de la tabla parameters).
     */
    private function agentId(): ?string
    {
        if (config('academic.openai.agent_id_parameter')) {
            return $this->agentIdFromParameter();
        }

        $agentId = trim((string) config('academic.openai.agent_id'));

        return $agentId !== '' ? $agentId : null;
    }

    protected function agentIdFromParameter(): ?string
    {
        $parameterCode = config('academic.openai.agent_id_parameter');

        if (!$parameterCode) {
            return null;
        }

        $value = trim((string) $this->parameterValue($parameterCode));

        return $value !== '' ? $value : null;
    }

    private function agentSessionMinutes(): int
    {
        return max(1, (int) config('academic.openai.agent_session_minutes', 720));
    }

    private function agentSessionCacheKey(int|string $userId): string
    {
        return 'academic:openai-agent-session:' . $userId;
    }

    private function requestResponses(int|string $userId, array $input, ?string $instructions = null, bool $continueThread = true): array
    {
        $payload = [
            'model' => $this->model(),
            'input' => $input,
        ];

        if ($instructions) {
            $payload['instructions'] = $instructions;
        }

        $previousResponseId = $continueThread ? Cache::get($this->responseCacheKey($userId)) : null;

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

        $parameterCode = config('academic.openai.api_key_parameter', 'IA-00001');

        return 'Error al comunicarse con OpenAI (' . $exception->response->status() . '). Verifica la API Key del parametro ' . $parameterCode . '.';
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
            $parameterCode = config('academic.openai.api_key_parameter', 'IA-00001');
            throw new RuntimeException('Falta configurar la API Key de OpenAI en el parametro ' . $parameterCode . ' del sistema.');
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

        $value = trim((string) $this->parameterValue($parameterCode));

        return $value !== '' ? $value : null;
    }

    /**
     * Lee un parametro del sistema con una cache corta. El controlador de
     * Parametros invalida esta clave al guardar para que el cambio sea inmediato.
     */
    protected function parameterValue(?string $parameterCode): ?string
    {
        if (!$parameterCode) {
            return null;
        }

        $value = Cache::remember(
            'academic:parameter:' . $parameterCode,
            now()->addMinutes(60),
            function () use ($parameterCode) {
                return Parameter::where('parameter_code', $parameterCode)->value('value_default');
            }
        );

        return $value !== null ? (string) $value : null;
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
