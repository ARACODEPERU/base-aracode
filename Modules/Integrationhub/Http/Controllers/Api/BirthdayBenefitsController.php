<?php

namespace Modules\Integrationhub\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\DispatchBirthdayBenefitIntegration;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\Integrationhub\Entities\IntegrationEndpoint;

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

        foreach (['create_contact', 'start_flow_birthdays'] as $endpointName) {
            $matchingEndpoints = IntegrationEndpoint::where('name', $endpointName)->get();

            if ($matchingEndpoints->isEmpty()) {
                return response()->json([
                    'message' => "No existe el endpoint de Integrationhub {$endpointName}.",
                ], 404);
            }

            if ($matchingEndpoints->count() > 1) {
                return response()->json([
                    'message' => "El endpoint {$endpointName} esta duplicado. Corrige los endpoints antes de ejecutar esta API.",
                ], 422);
            }
        }

        $today = now();
        $people = Person::query()
            ->whereNotNull('birthdate')
            ->whereMonth('birthdate', $today->month)
            ->whereDay('birthdate', $today->day)
            ->get(['id']);

        $extraVariables = array_replace(
            $validated['extra_variables'] ?? [],
            $validated['variables'] ?? []
        );
        $flowId = $validated['flow_id'] ?? $extraVariables['flow_id'] ?? null;

        if (empty($flowId)) {
            return response()->json([
                'message' => 'Debes enviar flow_id para ejecutar start_flow_birthdays.',
            ], 422);
        }

        unset($extraVariables['flow_id']);

        foreach ($people as $person) {
            DispatchBirthdayBenefitIntegration::dispatch(
                $person->id,
                $flowId,
                $extraVariables,
                $trackResults,
                $batchId
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Ejecuciones enviadas a la cola.',
            'endpoints' => ['create_contact', 'start_flow_birthdays'],
            'birthday_date' => $today->toDateString(),
            'queued' => $people->count(),
            'track_results' => $trackResults,
            'batch_id' => $batchId,
            'results_location' => $trackResults ? 'Pestaña Historial, filtrando por batch_id.' : null,
        ]);
    }
}
