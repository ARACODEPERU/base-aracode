<?php

namespace Modules\Integrationhub\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Inertia\Inertia;
use Modules\Integrationhub\Entities\Integration;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\DB;
use Modules\Integrationhub\Entities\IntegrationAuth;
use Modules\Integrationhub\Entities\IntegrationEndpoint;
use Modules\Integrationhub\Entities\IntegrationFieldMap;
use Modules\Integrationhub\Entities\IntegrationSchedule;
use Modules\Integrationhub\Entities\IntegrationQuery;
use Modules\Integrationhub\Entities\IntegrationExecLog;
use Illuminate\Validation\ValidationException;
use App\Services\IntegrationhubCronExpression;
use Illuminate\Support\Facades\Route;

class IntegrationhubController extends Controller
{
    use ValidatesRequests;

    /**
     * Patrones de keys que se consideran sensibles para enmascarar en logs.
     */
    private const SENSITIVE_KEY_PATTERNS = [
        'password', 'passwd', 'pwd',
        'token', 'access_token', 'refresh_token',
        'api_key', 'apikey', 'api-key',
        'secret', 'client_secret',
        'authorization',
        'credential', 'bearer',
        'private_key', 'privatekey',
    ];

    public function index()
    {
        $integrations = Integration::when(request()->search, function($query){
            $query->where('name', 'like', '%' . request()->search . '%');
        })->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('Integrationhub::Integration/Index', [
            'integrations' => $integrations,
            'filters' => request()->all('search')
        ]);
    }

    public function create()
    {
        return Inertia::render('Integrationhub::Integration/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'url_base' => 'required|url|max:500',
            'description' => 'nullable|string',
            'execution_type' => 'required|in:manual,scheduled,webhook',
            'is_active' => 'boolean',
            'timeout' => 'nullable|integer|min:5|max:300',
            'retry_attempts' => 'nullable|integer|min:0|max:10'
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        // Guardar configuraciones adicionales en el campo config
        $config = [
            'timeout' => $validated['timeout'] ?? 30,
            'retry_attempts' => $validated['retry_attempts'] ?? 3
        ];
        $validated['config'] = $config;

        // Remover campos que no existen como columnas
        unset($validated['timeout'], $validated['retry_attempts']);

        $integration = Integration::create($validated);

        return redirect()->route('integrationhub_editar', $integration->id);
    }

    public function edit(int $id)
    {
        $integration = Integration::with([
            'auths',
            'endpoints',
            'endpoints.fieldMaps',
            'queries',
            'schedules.endpoint',
        ])->findOrFail($id);

        $recentLogIds = $integration->logs()
            ->orderBy('executed_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->pluck('id');

        $recentBatchIds = $integration->logs()
            ->whereNotNull('batch_id')
            ->orderBy('executed_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit(5)
            ->pluck('batch_id')
            ->unique()
            ->values();

        $integration->setRelation(
            'logs',
            $integration->logs()
                ->where(function ($query) use ($recentLogIds, $recentBatchIds) {
                    $query->whereIn('id', $recentLogIds)
                        ->orWhereIn('batch_id', $recentBatchIds);
                })
                ->orderBy('executed_at', 'desc')
                ->orderBy('id', 'desc')
                ->get()
        );

        return Inertia::render('Integrationhub::Integration/Edit', [
            'integration' => $integration,
            'apiRoutes' => $this->integrationhubApiRoutes(),
        ]);
    }

    private function integrationhubApiRoutes(): array
    {
        return collect(Route::getRoutes())
            ->filter(function ($route) {
                return str_starts_with($route->uri(), 'api/')
                    && str_contains($route->uri(), 'integrationhub');
            })
            ->map(function ($route) {
                $methods = collect($route->methods())
                    ->reject(fn ($method) => $method === 'HEAD')
                    ->values()
                    ->all();

                return [
                    'methods' => $methods,
                    'method' => implode('|', $methods),
                    'uri' => '/' . ltrim($route->uri(), '/'),
                    'name' => $route->getName(),
                    'action' => $route->getActionName() !== 'Closure' ? $route->getActionName() : 'Closure',
                    'middleware' => array_values($route->gatherMiddleware()),
                ];
            })
            ->sortBy('uri')
            ->values()
            ->all();
    }

    public function update(Request $request, int $id)
    {
        $integration = Integration::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'url_base' => 'required|url|max:500',
            'description' => 'nullable|string',
            'execution_type' => 'required|in:manual,scheduled,webhook',
            'is_active' => 'boolean',
            'timeout' => 'nullable|integer|min:5|max:300',
            'retry_attempts' => 'nullable|integer|min:0|max:10'
        ]);

        $validated['is_active'] = $validated['is_active'] ?? $integration->is_active;

        // Guardar configuración adicional en el campo config
        $config = $integration->config ?? [];
        $config['timeout'] = $validated['timeout'] ?? 30;
        $config['retry_attempts'] = $validated['retry_attempts'] ?? 3;
        $validated['config'] = $config;

        // Remover campos que no existen como columnas
        unset($validated['timeout'], $validated['retry_attempts']);

        $integration->update($validated);

    }

    public function destroy(int $id)
    {
        $integration = Integration::findOrFail($id);
        $integration->delete();

        return response()->json([
            'success' => true,
            'message' => 'Integración eliminada correctamente'
        ]);
    }

    public function execute(Request $request, int $id)
    {
        $integration = Integration::findOrFail($id);

        $this->validate($request, [
            'endpoint_id' => 'required|integer',
            'field_values' => 'nullable|array',
            'extra_params' => 'nullable|array',
            'track_results' => 'nullable|boolean',
            'batch_id' => 'nullable|string|max:80',
        ]);
        $trackResults = $request->has('track_results') ? $request->boolean('track_results') : true;

        $endpoint = IntegrationEndpoint::where('id', $request->endpoint_id)
            ->where('integration_id', $id)
            ->firstOrFail();
        // Start building the request

        $url = rtrim($integration->url_base, '/') . '/' . ltrim($endpoint->endpoint_path, '/');

        // Get enabled field maps ordered by sort_order
        $fieldMaps = $endpoint->fieldMaps()->where('is_enabled', true)->orderBy('sort_order')->get();
        $fieldValueOverrides = collect(array_replace(
            $request->input('field_values', []),
            $request->input('variables', [])
        ));
        $extraParams = collect($request->input('extra_params', []));
        $extraPathParams = collect($extraParams->get('path', []));
        $extraQueryParams = collect($extraParams->get('query', []));
        $extraBodyParams = collect($extraParams->get('body', []));
        $extraHeaderParams = collect($extraParams->get('headers', []));
        $usesBody = $endpoint->http_method !== 'GET' || $endpoint->body_type != 'none';
        $configuredVariableKeys = $fieldMaps
            ->flatMap(fn ($fieldMap) => [
                (string) $fieldMap->id,
                $fieldMap->id,
                $fieldMap->field_key,
            ])
            ->filter(fn ($key) => !is_null($key) && $key !== '')
            ->map(fn ($key) => (string) $key)
            ->unique();
        $additionalVariables = $fieldValueOverrides->reject(function ($value, $key) use ($configuredVariableKeys) {
            return $configuredVariableKeys->contains((string) $key);
        });

        $hasOverride = function ($fieldMap) use ($fieldValueOverrides) {
            return $fieldValueOverrides->has((string) $fieldMap->id)
                || $fieldValueOverrides->has($fieldMap->id)
                || $fieldValueOverrides->has($fieldMap->field_key);
        };

        $getOverrideValue = function ($fieldMap) use ($fieldValueOverrides) {
            if ($fieldValueOverrides->has((string) $fieldMap->id)) {
                return $fieldValueOverrides->get((string) $fieldMap->id);
            }

            if ($fieldValueOverrides->has($fieldMap->id)) {
                return $fieldValueOverrides->get($fieldMap->id);
            }

            if ($fieldValueOverrides->has($fieldMap->field_key)) {
                return $fieldValueOverrides->get($fieldMap->field_key);
            }

            return null;
        };

        $hasFilledOverride = function ($fieldMap) use ($getOverrideValue, $hasOverride) {
            if (!$hasOverride($fieldMap)) {
                return false;
            }

            $value = $getOverrideValue($fieldMap);

            if (is_array($value)) {
                return !empty($value);
            }

            return !is_null($value) && trim((string) $value) !== '';
        };

        $resolveFieldValue = function ($fieldMap) use ($fieldValueOverrides, $hasFilledOverride, $getOverrideValue) {
            if ($fieldMap->source_type === 'query') {
                if ($hasFilledOverride($fieldMap)) {
                    return $getOverrideValue($fieldMap);
                }

                return $this->executeReadOnlyFieldQuery($fieldMap->field_value, $fieldMap->default_value);
            }

            if ($hasFilledOverride($fieldMap)) {
                return $getOverrideValue($fieldMap);
            }

            $fieldValue = $fieldMap->field_value;
            $defaultValue = $fieldMap->default_value;

            if (!is_null($fieldValue) && trim((string) $fieldValue) !== '') {
                return $fieldValue;
            }

            if (!is_null($defaultValue) && trim((string) $defaultValue) !== '') {
                return $defaultValue;
            }

            return null;
        };

        // Path parameters replace placeholders like {contact_id} in the endpoint path.
        foreach ($fieldMaps->where('field_location', 'path') as $fieldMap) {
            $pathValue = $resolveFieldValue($fieldMap) ?? '';
            $placeholder = '{' . $fieldMap->field_key . '}';

            if (!str_contains($url, $placeholder)) {
                throw new \InvalidArgumentException("La ruta del endpoint debe contener el placeholder {$placeholder} para reemplazar el campo de Ruta URL.");
            }

            $url = str_replace($placeholder, rawurlencode((string) $pathValue), $url);
        }

        foreach ($extraPathParams as $key => $value) {
            $url = str_replace('{' . $key . '}', rawurlencode((string) $value), $url);
        }

        // Query parameters
        $queryParams = [];

        foreach ($fieldMaps->where('field_location', 'query') as $fieldMap) {
            $sqlOriginal = $fieldMap->field_value; // "select number from people where id = ?"
            $defaultValue = $fieldMap->default_value; // "10"
            $finalValue = null;

            if ($fieldMap->source_type === 'query') {
                $queryParams[$fieldMap->field_key] = $resolveFieldValue($fieldMap);
                continue;
            }

            if ($fieldMap->source_type === 'query' && !$this->isReadOnlySelectSql($sqlOriginal)) {
                throw new \InvalidArgumentException('Solo se permiten consultas SELECT en mapeos de Integrationhub.');
            }

            if ($hasOverride($fieldMap)) {
                $finalValue = $resolveFieldValue($fieldMap);
            // 1. Verificamos si es una consulta SQL
            } else {
                // Si no es un SQL, simplemente usamos el valor o el default
                $finalValue = $resolveFieldValue($fieldMap);
            }

            // 4. Guardamos el valor REAL obtenido en el array de parámetros
            $queryParams[$fieldMap->field_key] = $finalValue;
        }

        if (!$usesBody) {
            $queryParams = array_merge($queryParams, $additionalVariables->toArray());
        }

        $queryParams = array_merge($queryParams, $extraQueryParams->toArray());

        if (!empty($queryParams)) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($queryParams);
        }

        // Get enabled auth fields
        $authFields = $integration->auths()->where('is_enabled', true)->get();

        $headers = [];
        $body = [];

        foreach ($authFields as $auth) {
            // Manejo especial para Basic Auth
            if ($auth->field_type === 'basic_auth' && $auth->auth_location === 'header') {
                // Formato esperado en field_value: "username:password"
                if (!empty($auth->field_value) && strpos($auth->field_value, ':') !== false) {
                    $encoded = base64_encode($auth->field_value);
                    $headers['Authorization'] = 'Basic ' . $encoded;
                } else {
                    // Si no tiene formato username:password, usar el valor tal cual
                    $headers[$auth->field_name] = $auth->field_value;
                }
            } elseif ($auth->auth_location === 'header') {
                $headers[$auth->field_name] = $auth->field_value;
            } elseif ($auth->auth_location === 'body') {
                $body[$auth->field_name] = $auth->field_value;
            } elseif ($auth->auth_location === 'query') {
                $url .= (strpos($url, '?') !== false ? '&' : '?') . $auth->field_name . '=' . $auth->field_value;
            }
        }

        $headers = array_merge($headers, $extraHeaderParams->toArray());

        // Body fields (only for POST/PUT/PATCH or when body_type != none)
        if ($usesBody) {
            foreach ($fieldMaps->where('field_location', 'body') as $fieldMap) {
                // Nuevo: Manejar subitems (configuración tipo tabla/campos)
                if ($fieldMap->has_subitems && !empty($fieldMap->subitems)) {
                    if ($hasOverride($fieldMap)) {
                        $body[$fieldMap->field_key] = $resolveFieldValue($fieldMap);
                        continue;
                    }

                    $subitems = $fieldMap->subitems;

                    // Filtrar solo habilitados y ordenar
                    $subitems = array_filter($subitems, fn($item) => $item['is_enabled'] ?? true);
                    usort($subitems, fn($a, $b) => ($a['sort_order'] ?? 0) - ($b['sort_order'] ?? 0));

                    $result = [];

                    // Agrupar por tabla para hacer una sola consulta por tabla
                    $tableGroups = [];
                    foreach ($subitems as $item) {
                        if (($item['source_type'] ?? '') === 'database' || ($item['field_type'] ?? '') === 'table_field') {
                            $table = $item['source_table'] ?? null;
                            if ($table) {
                                $tableGroups[$table][] = $item;
                            }
                        } else {
                            // Valor estático
                            $result[$item['field_key']] = $item['default_value'] ?? $item['field_value'] ?? '';
                        }
                    }

                    // Ejecutar consultas agrupadas por tabla
                    foreach ($tableGroups as $table => $items) {
                        $selects = array_unique(array_column($items, 'source_field'));
                        $selects = array_filter($selects);
                        if (empty($selects)) continue;

                        $query = 'SELECT ' . implode(', ', $selects) . ' FROM ' . $table;

                        try {
                            if ($fieldMap->structure_type === 'array') {
                                $rows = DB::select($query);
                                $arrayResult = [];
                                foreach ($rows as $row) {
                                    $rowData = [];
                                    foreach ($items as $item) {
                                        $rowData[$item['field_key']] = $row->{$item['source_field']} ?? $item['default_value'] ?? '';
                                    }
                                    $arrayResult[] = $rowData;
                                }
                                $result = $arrayResult;
                            } else {
                                $data = DB::selectOne($query);
                                if ($data) {
                                    foreach ($items as $item) {
                                        $result[$item['field_key']] = $data->{$item['source_field']} ?? $item['default_value'] ?? '';
                                    }
                                } else {
                                    foreach ($items as $item) {
                                        $result[$item['field_key']] = $item['default_value'] ?? '';
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            foreach ($items as $item) {
                                $result[$item['field_key']] = $item['default_value'] ?? '';
                            }
                        }
                    }

                    $body[$fieldMap->field_key] = $result;
                    continue;
                }

                // Lógica existente para field maps sin subitems
                $sqlOriginal = $fieldMap->field_value;
                $defaultValue = $fieldMap->default_value;

                if ($fieldMap->source_type === 'query') {
                    $body[$fieldMap->field_key] = $resolveFieldValue($fieldMap);
                } elseif ($hasOverride($fieldMap)) {
                    $body[$fieldMap->field_key] = $resolveFieldValue($fieldMap);
                } else {
                    $body[$fieldMap->field_key] = $resolveFieldValue($fieldMap);
                }
            }
        }

        if ($usesBody) {
            $body = array_merge($body, $additionalVariables->toArray());
        }

        $body = array_merge($body, $extraBodyParams->toArray());

        // Header fields from field maps

        foreach ($fieldMaps->where('field_location', 'header') as $fieldMap) {
            $headers[$fieldMap->field_key] = $resolveFieldValue($fieldMap);
        }

        $startTime = microtime(true);

        // Recopilar nombres de campos sensibles (auth)
        $sensitiveKeys = $authFields
            ->filter(fn ($auth) => in_array($auth->field_type, ['password', 'token', 'api_key', 'oauth', 'basic_auth']))
            ->pluck('field_name')
            ->values()
            ->all();

        // Enmascarar la URL para proteger query params sensibles
        $maskedUrl = $this->maskUrlSensitiveParams($url, $sensitiveKeys);

        $logData = [
            'integration_id' => $id,
            'endpoint_id' => $endpoint->id,
            'batch_id' => $request->input('batch_id'),
            'executed_at' => now(),
        ];

        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => $integration->config['timeout'] ?? 30
            ]);

            $options = [
                'headers' => array_merge($headers, [
                    'Accept' => 'application/json'
                ]),
                'verify' => false
            ];

            // Enviar body según el tipo configurado en el endpoint
            if (!empty($body)) {
                $hasContentType = collect(array_keys($options['headers']))
                    ->contains(fn ($header) => strtolower($header) === 'content-type');

                switch ($endpoint->body_type) {
                    case 'json':
                        if (!$hasContentType) {
                            $options['headers']['Content-Type'] = 'application/json';
                        }
                        $options['json'] = $body;
                        break;

                    case 'form':
                        if (!$hasContentType) {
                            $options['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
                        }
                        $options['form_params'] = $body;
                        break;

                    case 'xml':
                        $xml = new \SimpleXMLElement('<root/>');
                        $this->arrayToXml($body, $xml);
                        $options['body'] = $xml->asXML();
                        if (!$hasContentType) {
                            $options['headers']['Content-Type'] = 'application/xml';
                        }
                        break;

                    case 'raw':
                        $rawValue = reset($body);
                        $options['body'] = is_array($rawValue) ? json_encode($rawValue) : (string)$rawValue;
                        if (!$hasContentType) {
                            $options['headers']['Content-Type'] = 'text/plain';
                        }
                        break;
                }
            }

            $requestData = [
                'method' => $endpoint->http_method,
                'url' => $maskedUrl,
                'headers' => $options['headers'] ?? [],
                'query' => $queryParams,
                'body_type' => $endpoint->body_type,
                'body' => $body,
            ];

            $logData['request_payload'] = $this->maskSensitiveData($requestData, $sensitiveKeys);

            $response = $client->request($endpoint->http_method, $url, $options);

            $executionTime = round((microtime(true) - $startTime) * 1000);
            $statusCode = $response->getStatusCode();
            $rawResponseBody = $response->getBody()->getContents();
            $responseBody = json_decode($rawResponseBody, true);
            $responseBody = json_last_error() === JSON_ERROR_NONE ? $responseBody : $rawResponseBody;
            $receivedData = [
                'status_code' => $statusCode,
                'headers' => $response->getHeaders(),
                'body' => $responseBody,
            ];

            // Save success log
            $logData['status'] = $statusCode >= 200 && $statusCode < 300 ? 'success' : 'failed';
            $logData['response_body'] = $this->maskSensitiveData($responseBody, $sensitiveKeys);
            $logData['response_status_code'] = $statusCode;
            $logData['execution_time_ms'] = $executionTime;

            if ($trackResults) {
                $integration->logs()->create($logData);
                $this->pruneIntegrationLogs($integration);
            }

            return response()->json([
                'message' => 'Integración ejecutada correctamente',
                'status_code' => $statusCode,
                'request' => $requestData,
                'received' => $receivedData,
                'response' => $responseBody,
                'executed_at' => now()
            ]);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000);
            $statusCode = $e->getResponse()->getStatusCode();
            $rawResponseBody = $e->getResponse()->getBody()->getContents();
            $responseBody = json_decode($rawResponseBody, true);
            $responseBody = json_last_error() === JSON_ERROR_NONE ? $responseBody : $rawResponseBody;
            $receivedData = [
                'status_code' => $statusCode,
                'headers' => $e->getResponse()->getHeaders(),
                'body' => $responseBody,
            ];
            $externalMessage = is_array($responseBody)
                ? ($responseBody['message'] ?? $responseBody['error'] ?? null)
                : trim(preg_replace('/\s+/', ' ', strip_tags((string) $responseBody)));
            $externalMessage = $externalMessage ?: $e->getMessage();

            // Save failed log
            $logData['status'] = 'failed';
            $logData['response_body'] = $this->maskSensitiveData($responseBody, $sensitiveKeys);
            $logData['response_status_code'] = $statusCode;
            $logData['execution_time_ms'] = $executionTime;
            $logData['error_message'] = $externalMessage;

            if ($trackResults) {
                $integration->logs()->create($logData);
                $this->pruneIntegrationLogs($integration);
            }

            return response()->json([
                'message' => 'Error en la solicitud externa: ' . $externalMessage,
                'status_code' => $statusCode,
                'request' => $requestData ?? [
                    'method' => $endpoint->http_method,
                    'url' => $url,
                    'headers' => $headers,
                    'query' => $queryParams,
                    'body_type' => $endpoint->body_type,
                    'body' => $body,
                ],
                'received' => $receivedData,
                'response' => $responseBody,
                'executed_at' => now()
            ], $statusCode);

        } catch (\Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000);

            // Save failed log
            $logData['status'] = 'failed';
            $logData['response_status_code'] = 500;
            $logData['execution_time_ms'] = $executionTime;
            $logData['error_message'] = $e->getMessage();

            if ($trackResults) {
                $integration->logs()->create($logData);
                $this->pruneIntegrationLogs($integration);
            }

            return response()->json([
                'message' => 'Error al ejecutar la integración: ' . $e->getMessage(),
                'status_code' => 500,
                'request' => $requestData ?? [
                    'method' => $endpoint->http_method,
                    'url' => $url,
                    'headers' => $headers,
                    'query' => $queryParams,
                    'body_type' => $endpoint->body_type,
                    'body' => $body,
                ],
                'received' => null,
                'response' => null,
                'executed_at' => now()
            ], 500);
        }
    }

    public function executeByName(Request $request, string $integration, string $endpoint)
    {
        $integrationModel = Integration::where('name', urldecode($integration))->firstOrFail();

        $endpointModel = $integrationModel->endpoints()
            ->where('name', urldecode($endpoint))
            ->firstOrFail();

        $request->merge([
            'endpoint_id' => $endpointModel->id,
        ]);

        return $this->execute($request, $integrationModel->id);
    }

    public function executeByEndpointName(Request $request, string $endpoint)
    {
        $matchingEndpoints = IntegrationEndpoint::where('name', urldecode($endpoint))->get();

        $decodedEndpoint = urldecode($endpoint);

        if ($matchingEndpoints->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "No existe el endpoint '{$decodedEndpoint}' en ninguna integración.",
            ], 404);
        }

        if ($matchingEndpoints->count() > 1) {
            return response()->json([
                'success' => false,
                'message' => "El endpoint '{$decodedEndpoint}' está duplicado en varias integraciones. Corrige los endpoints antes de ejecutar.",
            ], 422);
        }

        $endpointModel = $matchingEndpoints->first();

        $request->merge([
            'endpoint_id' => $endpointModel->id,
        ]);

        return $this->execute($request, $endpointModel->integration_id);
    }

    /**
     * Método helper para ejecutar un endpoint por nombre sin necesidad de
     * construir un objeto Request manualmente.
     *
     * @param  string       $endpoint     Nombre exacto del endpoint en Integrationhub
     * @param  array        $fieldValues  Variables obligatorias y opcionales del endpoint (field_values)
     * @param  array        $extraParams  Parámetros extra opcionales (path, query, body, headers)
     * @param  bool         $trackResults Si true, guarda el log de ejecución
     * @param  string|null  $batchId      ID de lote opcional para agrupar ejecuciones
     * @return \Illuminate\Http\JsonResponse
     *
     * @example
     *   $resultado = $controller->runEndpoint('create_contact', [
     *       'phone' => '5551234567',
     *       'email' => 'user@example.com',
     *   ]);
     */
    public function runEndpoint(
        string $endpoint,
        array  $fieldValues = [],
        array  $extraParams = [],
        bool   $trackResults = true,
        ?string $batchId = null
    ) {
        $requestData = [
            'field_values'  => $fieldValues,
            'extra_params'  => $extraParams,
            'track_results' => $trackResults,
        ];

        if ($batchId !== null) {
            $requestData['batch_id'] = $batchId;
        }

        $request = Request::create('/', 'POST', $requestData);

        return $this->executeByEndpointName($request, $endpoint);
    }

    public function logs(Request $request, int $id)
    {
        Integration::findOrFail($id);

        $query = IntegrationExecLog::with('endpoint');

        if (!$request->filled('batch_id')) {
            $query->where('integration_id', $id);
        }

        if ($request->filled('batch_id')) {
            $query->where('batch_id', $request->input('batch_id'));
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('batch_id', 'like', "%{$search}%")
                    ->orWhere('error_message', 'like', "%{$search}%")
                    ->orWhereHas('endpoint', function ($endpointQuery) use ($search) {
                        $endpointQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return response()->json([
            'logs' => $query
                ->orderBy('executed_at', 'desc')
                ->orderBy('id', 'desc')
                ->limit((int) $request->input('limit', 200))
                ->get(),
        ]);
    }

    public function updateAuth(Request $request, int $id)
    {
        $integration = Integration::findOrFail($id);

        $this->validate($request, [
            'field_id' => 'nullable|integer',
            'field_name' => 'required|string|max:100',
            'field_type' => 'required|in:text,password,token,api_key,oauth,basic_auth',
            'field_value' => 'nullable|string',
            'auth_location' => 'required|in:header,body,query',
            'is_enabled' => 'boolean'
        ]);

        if ($request->input('field_id')) {
            $auth = IntegrationAuth::where('id', $request->field_id)->firstOrFail();
            $auth->update([
                'field_name' => $request->field_name,
                'field_type' => $request->field_type,
                'field_value' => $request->field_value ?? null,
                'auth_location' => $request->auth_location,
                'is_enabled' => $request->is_enabled ?? true
            ]);
        } else {
            $index = $integration->auths()->count() + 1;
            $integration->auths()->create([
                'field_name' => $request->field_name,
                'field_type' => $request->field_type,
                'field_value' => $request->field_value ?? null,
                'auth_location' => $request->auth_location,
                'is_enabled' => $request->is_enabled ?? true,
                'sort_order' => $index
            ]);
        }
    }

    public function destroyAuth(int $id)
    {
        $auth = IntegrationAuth::where('id', $id)->firstOrFail();
        $auth->delete();

        return response()->json([
            'message' => 'Campo de autenticación eliminado correctamente'
        ]);
    }

    public function updateStatusAuth(int $id, Request $request)
    {
        $fieldId = $request->input('field_id');
        $isActive = $request->input('is_active');

        $auth = IntegrationAuth::findOrFail($fieldId);
        $auth->is_enabled = $isActive;
        $auth->save();

        return response()->json([
            'message' => 'Estado del campo actualizado correctamente'
        ]);
    }

    public function updateEndpoints(Request $request, int $id)
    {
        $integration = Integration::findOrFail($id);

        $this->validate($request,
        [
            'name' => 'required|string|max:100',
            'endpoint_path' => 'required|string|max:255',
            'http_method' => 'required|in:GET,POST,PUT,DELETE,PATCH',
            'body_type' => 'required|in:json,xml,form,raw,none',
            'is_active' => 'boolean'
        ]);

        $duplicateEndpoint = IntegrationEndpoint::where('name', $request->name)
            ->when($request->input('endpoint_id'), function ($query) use ($request) {
                $query->where('id', '!=', $request->endpoint_id);
            })
            ->exists();

        if ($duplicateEndpoint) {
            throw ValidationException::withMessages([
                'name' => 'Ya existe un endpoint con ese nombre. Usa un nombre unico para poder ejecutarlo por API.',
            ]);
        }

        $index = $integration->endpoints()->count() + 1;

        if($request->input('endpoint_id')) {
            $endpoint = IntegrationEndpoint::where('id', $request->endpoint_id)->firstOrFail();
            $endpoint->update([
                'name' => $request->name,
                'endpoint_path' => $request->endpoint_path,
                'http_method' => $request->http_method,
                'body_type' => $request->body_type,
                'is_active' => $request->is_active ?? true
            ]);
        }else{
            $integration->endpoints()->create([
                'name' => $request->name,
                'endpoint_path' => $request->endpoint_path,
                'http_method' => $request->http_method,
                'body_type' => $request->body_type,
                'is_active' => $request->is_active ?? true,
                'sort_order' => $index
            ]);
        }
    }
    public function destroyEndpoints(int $id)
    {
        $endpoint = IntegrationEndpoint::where('id', $id)->firstOrFail();
        $endpoint->delete();

        return response()->json([
            'message' => 'Endpoint eliminado correctamente'
        ]);
    }
    public function updateStatusEndpoints(int $id, Request $request)
    {
        $integration = Integration::findOrFail($id);
        $endpointId = $request->input('endpoint_id');
        $isActive = $request->input('is_active');

        $endpoint = IntegrationEndpoint::findOrFail($endpointId);
        $endpoint->is_active = $isActive;
        $endpoint->save();

        return response()->json([
            'message' => 'Estado del endpoint actualizado correctamente'
        ]);
    }

    public function updateFieldMap(Request $request, int $id)
    {
        $integration = Integration::findOrFail($id);
        //dd($request->all());
        $this->validate($request, [
            'field_map_id' => 'nullable|integer',
            'endpoint_id' => 'required|integer',
            'field_key' => 'required|string|max:100',
            'field_value' => 'nullable|string',
            'field_type' => 'required|in:static,table_field,query,computed,custom',
            'field_location' => 'required|in:query,path,body,header',
            'source_type' => 'required|in:static,database,query,function',
            'source_table' => 'nullable|string|max:100',
            'source_field' => 'nullable|string|max:100',
            'default_value' => 'nullable|string|max:255',
            'is_required' => 'boolean',
            'is_enabled' => 'boolean',
            'has_subitems' => 'boolean',
            'subitems' => 'nullable|json',
            'structure_type' => 'nullable|in:object,array',
            'array_query' => 'nullable|string',
            'default_type' => 'nullable|in:static,variable,none',
            'default_table' => 'nullable|string|max:100',
            'default_field' => 'nullable|string|max:100'
        ]);

        $endpoint = IntegrationEndpoint::where('id', $request->endpoint_id)
            ->where('integration_id', $integration->id)
            ->firstOrFail();
        $fieldKey = trim((string) $request->field_key);

        if (preg_match('/^\{([^{}]+)\}$/', $fieldKey, $matches)) {
            $fieldKey = trim($matches[1]);
            $request->merge(['field_key' => $fieldKey]);
        }

        if ($request->field_location === 'path') {
            $placeholder = '{' . $fieldKey . '}';

            if (!str_contains($endpoint->endpoint_path, $placeholder)) {
                throw ValidationException::withMessages([
                    'field_key' => "La ruta del endpoint debe contener el placeholder {$placeholder}.",
                ]);
            }
        }

        if ($request->source_type === 'query' && !$this->isReadOnlySelectSql($request->field_value)) {
            throw ValidationException::withMessages([
                'field_value' => 'Solo se permiten consultas SELECT. No se aceptan INSERT, UPDATE, DELETE ni otras sentencias.',
            ]);
        }

        if ($request->filled('array_query') && !$this->isReadOnlySelectSql($request->array_query)) {
            throw ValidationException::withMessages([
                'array_query' => 'Solo se permiten consultas SELECT para arrays.',
            ]);
        }

        $this->validateSubitemQueries($request->subitems ? json_decode($request->subitems, true) : []);

        if ($request->input('field_map_id')) {
            $fieldMap = IntegrationFieldMap::where('id', $request->field_map_id)->firstOrFail();
            $fieldValue = $request->field_value ?? null;

            if ($request->source_type !== 'query'
                && $fieldMap->source_type === 'query'
                && trim((string) $fieldMap->field_value) === trim((string) $fieldValue)
            ) {
                $fieldValue = null;
            }

            $fieldMap->update([
                'endpoint_id' => $request->endpoint_id,
                'field_key' => $request->field_key,
                'field_value' => $fieldValue,
                'field_type' => $request->field_type,
                'field_location' => $request->field_location,
                'source_type' => $request->source_type,
                'source_table' => $request->source_table ?? null,
                'source_field' => $request->source_field ?? null,
                'default_value' => $request->default_value ?? null,
                'is_required' => $request->is_required ?? false,
                'is_enabled' => $request->is_enabled ?? true,
                'has_subitems' => $request->has_subitems ?? false,
                'subitems' => $request->subitems ?? null,
                'structure_type' => $request->structure_type ?? 'object',
                'array_query' => $request->array_query ?? null,
                'default_type' => $request->default_type ?? null,
                'default_table' => $request->default_table ?? null,
                'default_field' => $request->default_field ?? null
            ]);
        } else {
            $index = $endpoint->fieldMaps()->count() + 1;
            $endpoint->fieldMaps()->create([
                'endpoint_id' => $request->endpoint_id,
                'field_key' => $request->field_key,
                'field_value' => $request->field_value ?? null,
                'field_type' => $request->field_type,
                'field_location' => $request->field_location,
                'source_type' => $request->source_type,
                'source_table' => $request->source_table ?? null,
                'source_field' => $request->source_field ?? null,
                'default_value' => $request->default_value ?? null,
                'is_required' => $request->is_required ?? false,
                'is_enabled' => $request->is_enabled ?? true,
                'sort_order' => $index,
                'has_subitems' => $request->has_subitems ?? false,
                'subitems' => $request->subitems ?? null,
                'structure_type' => $request->structure_type ?? 'object',
                'array_query' => $request->array_query ?? null,
                'default_type' => $request->default_type ?? null,
                'default_table' => $request->default_table ?? null,
                'default_field' => $request->default_field ?? null
            ]);
        }
    }

    public function updateSubitems(Request $request, int $id)
    {
        $integration = Integration::findOrFail($id);

        $this->validate($request, [
            'field_map_id' => 'required|integer',
            'subitems' => 'nullable|json'
        ]);

        $subitems = $request->subitems ? json_decode($request->subitems, true) : null;
        $this->validateSubitemQueries($subitems ?? []);

        $fieldMap = IntegrationFieldMap::where('id', $request->field_map_id)->firstOrFail();
        $fieldMap->update([
            'subitems' => $subitems
        ]);

        return response()->json([
            'message' => 'Subitems actualizados correctamente'
        ]);
    }

    public function destroyFieldMap(int $id)
    {
        $fieldMap = IntegrationFieldMap::where('id', $id)->firstOrFail();
        $fieldMap->delete();

        return response()->json([
            'message' => 'Mapeo eliminado correctamente'
        ]);
    }

    public function updateStatusFieldMap(int $id, Request $request)
    {
        $fieldMapId = $request->input('field_map_id');
        $isActive = $request->input('is_active');

        $fieldMap = IntegrationFieldMap::findOrFail($fieldMapId);
        $fieldMap->is_enabled = $isActive;
        $fieldMap->save();

        return response()->json([
            'message' => 'Estado del mapeo actualizado correctamente'
        ]);
    }

    public function getTables()
    {
        // 1. Obtenemos el nombre de la base de datos actual desde tu archivo .env
        $dbName = env('DB_DATABASE');

        $tables = DB::table('information_schema.tables')
            ->select('TABLE_NAME as table_name')
            ->where('table_schema', $dbName)
            ->pluck('table_name')
            ->toArray();


        return response()->json([
            'tables' => array_values($tables)
        ]);
    }

    public function getTableColumns(Request $request)
    {
        $table = $request->input('table');

        if (!$table) {
            return response()->json(['columns' => []]);
        }

        try {
            $columns = DB::connection()->getSchemaBuilder()->getColumnListing($table);

            $columnDetails = [];
            foreach ($columns as $column) {
                $type = DB::connection()->getSchemaBuilder()->getColumnType($table, $column);
                $columnDetails[] = [
                    'name' => $column,
                    'type' => $type,
                    'comment' => null
                ];
            }

            return response()->json([
                'columns' => $columnDetails
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'columns' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    public function updateQuery(Request $request, int $id)
    {
        $integration = Integration::findOrFail($id);

        $this->validate($request, [
            'query_id' => 'nullable|integer',
            'query_name' => 'required|string|max:100',
            'query_sql' => 'required|string',
            'query_type' => 'required|in:select',
            'parameters' => 'nullable|json',
            'is_active' => 'boolean'
        ]);

        if (!$this->isReadOnlySelectSql($request->query_sql)) {
            throw ValidationException::withMessages([
                'query_sql' => 'Solo se permiten consultas SELECT.',
            ]);
        }

        if ($request->input('query_id')) {
            $query = IntegrationQuery::where('id', $request->query_id)->firstOrFail();
            $query->update([
                'query_name' => $request->query_name,
                'query_sql' => $request->query_sql,
                'query_type' => $request->query_type,
                'parameters' => $request->parameters ? json_decode($request->parameters) : null,
                'is_active' => $request->is_active ?? true
            ]);
        } else {
            $index = $integration->queries()->count() + 1;
            $integration->queries()->create([
                'query_name' => $request->query_name,
                'query_sql' => $request->query_sql,
                'query_type' => $request->query_type,
                'parameters' => $request->parameters ? json_decode($request->parameters) : null,
                'is_active' => $request->is_active ?? true
            ]);
        }

        return response()->json([
            'message' => 'Query guardada correctamente'
        ]);
    }

    public function destroyQuery(int $id)
    {
        $query = IntegrationQuery::where('id', $id)->firstOrFail();
        $query->delete();

        return response()->json([
            'message' => 'Query eliminada correctamente'
        ]);
    }

    public function updateStatusQuery(Request $request, int $id)
    {
        $query = IntegrationQuery::where('id', $id)->firstOrFail();
        $query->update([
            'is_active' => $request->input('is_active', true)
        ]);

        return response()->json([
            'message' => 'Estado actualizado correctamente'
        ]);
    }

    public function updateSchedule(Request $request, int $id)
    {
        $integration = Integration::findOrFail($id);

        $this->validate($request, [
            'schedule_id' => 'nullable|integer',
            'target_type' => 'required|in:integration_endpoint,module_api',
            'endpoint_id' => 'nullable|integer',
            'api_route_name' => 'nullable|string|max:150',
            'cron_expression' => 'required|string|max:100',
            'payload' => 'nullable|array',
            'is_active' => 'boolean'
        ]);

        if ($request->target_type === 'integration_endpoint' && $request->endpoint_id) {
            IntegrationEndpoint::where('id', $request->endpoint_id)
                ->where('integration_id', $integration->id)
                ->firstOrFail();
        }

        if ($request->target_type === 'module_api') {
            $apiRoute = collect($this->integrationhubApiRoutes())
                ->firstWhere('name', $request->api_route_name);

            if (!$apiRoute) {
                throw ValidationException::withMessages([
                    'api_route_name' => 'Selecciona una API REST valida del modulo Integrationhub.',
                ]);
            }
        }

        // Calcular próxima ejecución
        $nextExecution = app(IntegrationhubCronExpression::class)->nextRunDate($request->cron_expression);

        if ($request->input('schedule_id')) {
            $schedule = IntegrationSchedule::where('id', $request->schedule_id)->firstOrFail();
            $schedule->update([
                'target_type' => $request->target_type,
                'endpoint_id' => $request->target_type === 'integration_endpoint' ? $request->endpoint_id : null,
                'api_route_name' => $request->target_type === 'module_api' ? $request->api_route_name : null,
                'cron_expression' => $request->cron_expression,
                'payload' => $request->payload ?? [],
                'is_active' => $request->is_active ?? true,
                'next_execution_at' => $nextExecution
            ]);
        } else {
            $integration->schedules()->create([
                'target_type' => $request->target_type,
                'endpoint_id' => $request->target_type === 'integration_endpoint' ? $request->endpoint_id : null,
                'api_route_name' => $request->target_type === 'module_api' ? $request->api_route_name : null,
                'cron_expression' => $request->cron_expression,
                'payload' => $request->payload ?? [],
                'is_active' => $request->is_active ?? true,
                'next_execution_at' => $nextExecution
            ]);
        }

        return response()->json([
            'message' => 'Programación guardada correctamente'
        ]);
    }

    public function destroySchedule(int $id)
    {
        $schedule = IntegrationSchedule::where('id', $id)->firstOrFail();
        $schedule->delete();

        return response()->json([
            'message' => 'Programación eliminada correctamente'
        ]);
    }

    private function calculateNextExecution($cronExpression)
    {
        try {
            $parts = explode(' ', trim($cronExpression));
            if (count($parts) < 5) {
                return null;
            }

            $minute = $parts[0];
            $hour = $parts[1];
            $day = $parts[2];
            $month = $parts[3];
            $weekday = $parts[4];

            $now = now();
            $next = $now->copy()->startOfDay();

            // Parse minute
            if ($minute === '*') {
                $next->minute(0);
            } elseif (strpos($minute, '*/') === 0) {
                $interval = (int)substr($minute, 2);
                $next->minute(0);
                $minutesPassed = floor($now->minute / $interval);
                $next->minute(($minutesPassed + 1) * $interval);
            } else {
                $next->minute((int)$minute);
            }

            // Parse hour
            if ($hour === '*') {
                if ($minute !== '*') {
                    // Already set minute, move to next hour if needed
                    if ($next->minute < (int)$minute || ($next->minute == (int)$minute && $next->lte($now))) {
                        // Keep current hour setting
                    }
                }
                $next->hour(0);
            } elseif (strpos($hour, '*/') === 0) {
                $interval = (int)substr($hour, 2);
                $next->hour(0);
                $hoursPassed = floor($now->hour / $interval);
                $next->hour(($hoursPassed + 1) * $interval);
            } else {
                $next->hour((int)$hour);
            }

            // If the calculated time is in the past, add appropriate interval
            if ($next->lte($now)) {
                if ($minute !== '*' && strpos($minute, '*/') === false) {
                    $next->addHour();
                }
            }

            // If still in the past, just return now + 1 day as fallback
            if ($next->lte($now)) {
                return $now->addDay()->startOfDay();
            }

            return $next;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Convierte un array asociativo a XML para el body type 'xml'
     */
    private function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = 'item_' . $key;
            }
            $key = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
            if (is_array($value)) {
                $child = $xml->addChild($key);
                $this->arrayToXml($value, $child);
            } else {
                $xml->addChild($key, htmlspecialchars((string)$value));
            }
        }
    }

    private function executeReadOnlyFieldQuery(?string $sql, mixed $defaultValue = null): mixed
    {
        if (!$this->isReadOnlySelectSql($sql)) {
            throw new \InvalidArgumentException('Solo se permiten consultas SELECT en mapeos de Integrationhub.');
        }

        $bindings = array_fill(0, substr_count($sql, '?'), $defaultValue);
        $rows = DB::select(trim($sql), $bindings);

        if (empty($rows)) {
            return $defaultValue;
        }

        $firstRow = (array) $rows[0];

        return reset($firstRow);
    }

    private function pruneIntegrationLogs(Integration $integration): void
    {
        $keepIds = $integration->logs()
            ->whereNull('batch_id')
            ->orderBy('executed_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->pluck('id');

        $integration->logs()
            ->whereNull('batch_id')
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    private function isReadOnlySelectSql(?string $sql): bool
    {
        $sql = trim((string) $sql);

        if ($sql === '') {
            return false;
        }

        $withoutComments = preg_replace('/\/\*.*?\*\//s', '', $sql);
        $withoutComments = preg_replace('/--.*$/m', '', $withoutComments);
        $withoutComments = trim($withoutComments);

        if (str_contains(rtrim($withoutComments, ';'), ';')) {
            return false;
        }

        if (!preg_match('/^(select|with)\b/i', $withoutComments)) {
            return false;
        }

        $withoutStringLiterals = preg_replace('/\'(?:\'\'|[^\'])*\'|"(?:\\\\"|""|[^"])*"/', "''", $withoutComments);
        $writePatterns = [
            '/\binsert\s+into\b/i',
            '/\bupdate\s+[`"\[]?[a-zA-Z0-9_.$-]+[`"\]]?\s+set\b/i',
            '/\bdelete\s+from\b/i',
            '/\btruncate\s+table\b/i',
            '/\bcreate\s+table\b/i',
            '/\balter\s+table\b/i',
            '/\bdrop\s+table\b/i',
            '/\breplace\s+into\b/i',
            '/\bmerge\s+into\b/i',
            '/\bcall\s+[a-zA-Z0-9_.$-]+\b/i',
            '/\bgrant\s+.+\s+on\b/i',
            '/\brevoke\s+.+\s+on\b/i',
            '/\block\s+tables?\b/i',
            '/\bunlock\s+tables?\b/i',
        ];

        foreach ($writePatterns as $pattern) {
            if (preg_match($pattern, $withoutStringLiterals)) {
                return false;
            }
        }

        return true;
    }

    private function validateSubitemQueries(array $subitems): void
    {
        foreach ($subitems as $index => $subitem) {
            if (($subitem['source_type'] ?? null) === 'query' && !$this->isReadOnlySelectSql($subitem['field_value'] ?? null)) {
                throw ValidationException::withMessages([
                    'subitems' => 'Solo se permiten consultas SELECT en subitems. No se aceptan INSERT, UPDATE, DELETE, PATCH ni otras sentencias.',
                ]);
            }
        }
    }

    /**
     * Enmascara valores sensibles en los parámetros query de una URL.
     * Reemplaza el valor de cada parámetro sensible con 'x' por cada caracter.
     */
    private function maskUrlSensitiveParams(string $url, array $sensitiveKeys = []): string
    {
        $parts = parse_url($url);
        if (empty($parts['query'])) {
            return $url;
        }

        parse_str($parts['query'], $queryParams);
        $masked = false;

        foreach ($queryParams as $key => $value) {
            if ($this->isSensitiveKey($key, $sensitiveKeys) && is_string($value) && $value !== '') {
                $queryParams[$key] = str_repeat('x', strlen($value));
                $masked = true;
            }
        }

        if (!$masked) {
            return $url;
        }

        $queryString = http_build_query($queryParams);
        $baseUrl = $parts['scheme'] . '://' . ($parts['host'] ?? '') . ($parts['port'] ? ':' . $parts['port'] : '');
        $baseUrl .= $parts['path'] ?? '';

        return $baseUrl . '?' . $queryString;
    }

    /**
     * Determina si un nombre de campo es sensible (password, token, api_key, etc.).
     *
     * @param  string        $key           Nombre del campo a evaluar
     * @param  array         $sensitiveKeys Nombres de campos de auth conocidos como sensibles
     * @return bool
     */
    private function isSensitiveKey(string $key, array $sensitiveKeys = []): bool
    {
        $keyLower = strtolower(trim((string) $key));

        return in_array($keyLower, $sensitiveKeys, true)
            || in_array($keyLower, self::SENSITIVE_KEY_PATTERNS, true)
            || str_contains($keyLower, 'password')
            || str_contains($keyLower, 'token')
            || str_contains($keyLower, 'secret')
            || str_contains($keyLower, 'api_key')
            || str_contains($keyLower, 'apikey')
            || str_contains($keyLower, 'authorization');
    }

    /**
     * Enmascara valores sensibles (tokens, passwords, api_keys, etc.) en un array.
     * Reemplaza cada valor por 'x' por cada caracter del original.
     *
     * @param  mixed   $data           Array u objeto a procesar
     * @param  array   $sensitiveKeys  Nombres de campos de auth conocidos como sensibles
     * @return mixed
     */
    private function maskSensitiveData(mixed $data, array $sensitiveKeys = []): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        $masked = [];

        foreach ($data as $key => $value) {
            if ($this->isSensitiveKey($key, $sensitiveKeys) && is_string($value) && $value !== '') {
                $masked[$key] = str_repeat('x', strlen($value));
            } elseif (is_array($value)) {
                $masked[$key] = $this->maskSensitiveData($value, $sensitiveKeys);
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }
}
