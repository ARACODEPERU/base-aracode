<?php

namespace Modules\Integrationhub\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
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

class IntegrationhubController extends Controller
{
    use ValidatesRequests;

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

    public function store(Request $request): RedirectResponse
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

        Integration::create($validated);

    }

    public function edit(int $id)
    {
        $integration = Integration::with([
            'auths',
            'endpoints',
            'endpoints.fieldMaps',
            'queries',
            'schedules',
            'logs'
        ])->findOrFail($id);

        return Inertia::render('Integrationhub::Integration/Edit', [
            'integration' => $integration
        ]);
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

    public function destroy(int $id): RedirectResponse
    {
        $integration = Integration::findOrFail($id);
        $integration->delete();

        return redirect()->route('integrationhub_listado')
            ->with('success', 'Integración eliminada correctamente');
    }

    public function execute(Request $request, int $id)
    {
        $integration = Integration::findOrFail($id);

        $this->validate($request, [
            'endpoint_id' => 'required|integer'
        ]);

        $endpoint = IntegrationEndpoint::where('id', $request->endpoint_id)
            ->where('integration_id', $id)
            ->firstOrFail();

        // Start building the request
        $url = rtrim($integration->url_base, '/') . '/' . ltrim($endpoint->url, '/');
        
        // Get enabled auth fields
        $authFields = $integration->auths()->where('is_active', true)->get();
        $headers = [];
        $body = [];
        
        foreach ($authFields as $auth) {
            if ($auth->field_location === 'header') {
                $headers[$auth->field_name] = $auth->field_value;
            } elseif ($auth->field_location === 'body') {
                $body[$auth->field_name] = $auth->field_value;
            } elseif ($auth->field_location === 'query') {
                $url .= (strpos($url, '?') !== false ? '&' : '?') . $auth->field_name . '=' . $auth->field_value;
            }
        }

        // Get enabled field maps for this endpoint
        $fieldMaps = $endpoint->fieldMaps()->where('is_active', true)->get();
        
        foreach ($fieldMaps as $fieldMap) {
            $body[$fieldMap->field_key] = $fieldMap->field_value ?? $fieldMap->default_value;
        }

        $startTime = microtime(true);
        $logData = [
            'integration_id' => $id,
            'endpoint_id' => $endpoint->id,
            'executed_at' => now(),
            'request_payload' => $body
        ];

        try {
            $client = new \GuzzleHttp\Client([
                'timeout' => $integration->config['timeout'] ?? 30
            ]);

            $options = [
                'headers' => array_merge($headers, ['Content-Type' => 'application/json']),
                'json' => $body,
                'verify' => false
            ];

            $response = $client->request($endpoint->method, $url, $options);

            $executionTime = round((microtime(true) - $startTime) * 1000);
            $statusCode = $response->getStatusCode();
            $responseBody = json_decode($response->getBody()->getContents(), true);

            // Save success log
            $logData['status'] = $statusCode >= 200 && $statusCode < 300 ? 'success' : 'failed';
            $logData['response_body'] = $responseBody;
            $logData['response_status_code'] = $statusCode;
            $logData['execution_time_ms'] = $executionTime;

            $integration->logs()->create($logData);

            return response()->json([
                'message' => 'Integración ejecutada correctamente',
                'status_code' => $statusCode,
                'response' => $responseBody,
                'executed_at' => now()
            ]);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000);
            $statusCode = $e->getResponse()->getStatusCode();
            $responseBody = json_decode($e->getResponse()->getBody()->getContents(), true);

            // Save failed log
            $logData['status'] = 'failed';
            $logData['response_body'] = $responseBody;
            $logData['response_status_code'] = $statusCode;
            $logData['execution_time_ms'] = $executionTime;
            $logData['error_message'] = $responseBody['message'] ?? $e->getMessage();

            $integration->logs()->create($logData);

            return response()->json([
                'message' => 'Error en la solicitud: ' . ($responseBody['message'] ?? $e->getMessage()),
                'status_code' => $statusCode,
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

            $integration->logs()->create($logData);

            return response()->json([
                'message' => 'Error al ejecutar la integración: ' . $e->getMessage(),
                'status_code' => 500,
                'response' => null,
                'executed_at' => now()
            ], 500);
        }
    }

    public function updateAuth(Request $request, int $id)
    {
        $integration = Integration::findOrFail($id);

        $this->validate($request, [
            'field_id' => 'nullable|integer',
            'field_name' => 'required|string|max:100',
            'field_type' => 'required|in:text,password,token,api_key,oauth',
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
            'field_value' => 'nullable|string|max:255',
            'field_type' => 'required|in:static,table_field,query,computed,custom',
            'source_type' => 'required|in:static,database,query,function',
            'source_table' => 'nullable|string|max:100',
            'source_field' => 'nullable|string|max:100',
            'default_value' => 'nullable|string|max:255',
            'is_enabled' => 'boolean',
            'has_subitems' => 'boolean',
            'subitems' => 'nullable|json',
            'structure_type' => 'nullable|in:object,array',
            'array_query' => 'nullable|string',
            'default_type' => 'nullable|in:static,variable,none',
            'default_table' => 'nullable|string|max:100',
            'default_field' => 'nullable|string|max:100'
        ]);

        if ($request->input('field_map_id')) {
            $fieldMap = IntegrationFieldMap::where('id', $request->field_map_id)->firstOrFail();
            $fieldMap->update([
                'endpoint_id' => $request->endpoint_id,
                'field_key' => $request->field_key,
                'field_value' => $request->field_value ?? null,
                'field_type' => $request->field_type,
                'source_type' => $request->source_type,
                'source_table' => $request->source_table ?? null,
                'source_field' => $request->source_field ?? null,
                'default_value' => $request->default_value ?? null,
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
            $endpoint = IntegrationEndpoint::findOrFail($request->endpoint_id);
            $index = $endpoint->fieldMaps()->count() + 1;
            $endpoint->fieldMaps()->create([
                'endpoint_id' => $request->endpoint_id,
                'field_key' => $request->field_key,
                'field_value' => $request->field_value ?? null,
                'field_type' => $request->field_type,
                'source_type' => $request->source_type,
                'source_table' => $request->source_table ?? null,
                'source_field' => $request->source_field ?? null,
                'default_value' => $request->default_value ?? null,
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

        $fieldMap = IntegrationFieldMap::where('id', $request->field_map_id)->firstOrFail();
        $fieldMap->update([
            'subitems' => $request->subitems ? json_decode($request->subitems) : null
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
            'query_type' => 'required|in:select,raw_sql',
            'parameters' => 'nullable|json',
            'is_active' => 'boolean'
        ]);

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
            'cron_expression' => 'required|string|max:100',
            'is_active' => 'boolean'
        ]);

        // Calcular próxima ejecución
        $nextExecution = $this->calculateNextExecution($request->cron_expression);

        if ($request->input('schedule_id')) {
            $schedule = IntegrationSchedule::where('id', $request->schedule_id)->firstOrFail();
            $schedule->update([
                'cron_expression' => $request->cron_expression,
                'is_active' => $request->is_active ?? true,
                'next_execution_at' => $nextExecution
            ]);
        } else {
            $integration->schedules()->create([
                'cron_expression' => $request->cron_expression,
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
}
