<?php

namespace Modules\Integrationhub\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Person;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;

class BirthdayBenefitsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'flow_id' => 'nullable|string|max:150',
            'variables' => 'nullable|array',
            'extra_variables' => 'nullable|array',
            'track_results' => 'nullable|boolean',
        ]);

        $trackResults = $request->has('track_results') ? $request->boolean('track_results') : true;
        $batchId = $trackResults ? (string) Str::uuid() : null;

        $extraVariables = array_replace(
            $validated['extra_variables'] ?? [],
            $validated['variables'] ?? []
        );
        $flowId = $validated['flow_id'] ?? $extraVariables['flow_id'] ?? null;

        if (empty($flowId)) {
            return response()->json([
                'message' => 'Debes enviar flow_id para ejecutar los endpoints.',
            ], 422);
        }

        unset($extraVariables['flow_id']);

        $today = now();
        $people = Person::query()
            ->whereNotNull('birthdate')
            ->whereMonth('birthdate', $today->month)
            ->whereDay('birthdate', $today->day)
            ->get(['id']);

        $hub = app(IntegrationhubController::class);
        $cacheStore = Cache::store('database');
        $results = [];
        $skipped = 0;

        foreach ($people as $person) {
            // Cache diario: evitar envíos duplicados por person_id + flow_id
            $cacheKey = 'birthday_benefits_' . $person->id . '_' . $flowId;

            if ($cacheStore->has($cacheKey)) {
                $skipped++;
                $results[] = [
                    'person_id' => $person->id,
                    'status' => 'skipped',
                    'message' => 'Ya se procesó hoy para este flow_id.',
                ];
                continue;
            }

            // Marcar ANTES de ejecutar para evitar carrera
            $cacheStore->put($cacheKey, true, Carbon::now()->endOfDay());

            // 1. Crear contacto
            $contactResult = $hub->runEndpoint('create_contact', array_replace($extraVariables, [
                'contact_id' => (string) $person->id,
                'flow_id' => $flowId,
            ]), [], $trackResults, $batchId);
            $contactData = $contactResult->getData(true);

            // 2. Iniciar flow de cumpleaños
            $flowResult = $hub->runEndpoint('start_flow_birthdays', array_replace($extraVariables, [
                'contact_id' => (string) $person->id,
                'flow_id' => $flowId,
            ]), [], $trackResults, $batchId);
            $flowData = $flowResult->getData(true);

            $results[] = [
                'person_id' => $person->id,
                'status' => 'processed',
                'create_contact' => $contactData,
                'start_flow_birthdays' => $flowData,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Ejecuciones completadas.',
            'endpoints' => ['create_contact', 'start_flow_birthdays'],
            'birthday_date' => $today->toDateString(),
            'processed' => $people->count() - $skipped,
            'skipped' => $skipped,
            'track_results' => $trackResults,
            'batch_id' => $batchId,
            'results' => $results,
            'results_location' => $trackResults ? 'Pestaña Historial, filtrando por batch_id.' : null,
        ]);
    }
}
