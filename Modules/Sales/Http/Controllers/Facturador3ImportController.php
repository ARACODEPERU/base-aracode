<?php

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Modules\Sales\Entities\Facturador3ImportJob;
use Modules\Sales\Services\Facturador3\Facturador3ConnectionService;
use Modules\Sales\Services\Facturador3\Facturador3ImportService;
use Modules\Sales\Services\Facturador3\Facturador3PreviewService;

class Facturador3ImportController extends Controller
{
    public function preview(Request $request, Facturador3ConnectionService $connection, Facturador3PreviewService $preview)
    {
        $validated = $request->validate([
            'tenant_database' => 'required|string|max:100',
            'import_with_stock' => 'nullable|boolean',
            'import_without_stock' => 'nullable|boolean',
            'min_stock' => 'nullable|numeric|min:0',
        ]);

        try {
            $connection->configureTenantDatabase($validated['tenant_database']);
            $connection->testConnection();

            $missing = $connection->requiredTablesExist();
            if (! empty($missing)) {
                return response()->json([
                    'message' => 'Tablas faltantes: '.implode(', ', $missing),
                ], 422);
            }

            $summary = $preview->summary($validated['tenant_database'], $validated);
            $establishments = $connection->getEstablishments();

            $establishmentMap = [];
            $mapPath = storage_path('app/facturador3_establishment_map.json');
            if (file_exists($mapPath)) {
                $establishmentMap = json_decode(file_get_contents($mapPath), true) ?: [];
            }

            $exclusions = $preview->listExclusions($validated['tenant_database']);

            return response()->json([
                'summary' => $summary,
                'establishments' => $establishments,
                'establishment_map' => $establishmentMap,
                'exclusions' => $exclusions,
                'chunk_size' => (int) config('facturador3_import.chunk_size', 1000),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function search(Request $request, Facturador3PreviewService $preview)
    {
        $validated = $request->validate([
            'tenant_database' => 'required|string|max:100',
            'search' => 'required|string|min:2|max:200',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        try {
            $results = $preview->search(
                $validated['tenant_database'],
                $validated['search'],
                $validated['limit'] ?? 20
            );

            return response()->json(['results' => $results]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function saveEstablishmentMap(Request $request)
    {
        $validated = $request->validate([
            'establishment_map' => 'required|array',
            'establishment_map.*' => 'nullable|integer',
        ]);

        $map = [];
        foreach ($validated['establishment_map'] as $f3Id => $localId) {
            if ($localId) {
                $map[(int) $f3Id] = (int) $localId;
            }
        }

        File::put(
            storage_path('app/facturador3_establishment_map.json'),
            json_encode($map, JSON_PRETTY_PRINT)
        );

        return response()->json(['message' => 'Mapeo guardado.', 'establishment_map' => $map]);
    }

    public function saveExclusions(Request $request, Facturador3PreviewService $preview)
    {
        $validated = $request->validate([
            'tenant_database' => 'required|string|max:100',
            'f3_item_ids' => 'required|array',
            'f3_item_ids.*' => 'integer',
        ]);

        $preview->syncExclusions(
            $validated['tenant_database'],
            Auth::id(),
            $validated['f3_item_ids']
        );

        return response()->json([
            'message' => 'Exclusiones guardadas.',
            'exclusions' => $preview->listExclusions($validated['tenant_database']),
        ]);
    }

    public function removeExclusion(Request $request, Facturador3PreviewService $preview)
    {
        $validated = $request->validate([
            'tenant_database' => 'required|string|max:100',
            'f3_item_id' => 'required|integer',
        ]);

        $preview->removeExclusion($validated['tenant_database'], $validated['f3_item_id']);

        return response()->json([
            'message' => 'Exclusión eliminada.',
            'exclusions' => $preview->listExclusions($validated['tenant_database']),
        ]);
    }

    public function process(Request $request, Facturador3ImportService $importService)
    {
        $validated = $request->validate([
            'tenant_database' => 'required|string|max:100',
            'establishment_map' => 'required|array',
            'establishment_map.*' => 'nullable|integer',
            'import_with_stock' => 'nullable|boolean',
            'import_without_stock' => 'nullable|boolean',
            'min_stock' => 'nullable|numeric|min:0',
            'excluded_item_ids' => 'nullable|array',
            'excluded_item_ids.*' => 'integer',
            'chunk_size' => 'nullable|integer|min:100|max:5000',
        ]);

        $map = array_filter(
            array_map('intval', $validated['establishment_map']),
            fn ($value) => $value > 0
        );

        if (empty($map)) {
            return response()->json(['message' => 'Debe mapear al menos un establecimiento.'], 422);
        }

        File::put(
            storage_path('app/facturador3_establishment_map.json'),
            json_encode($map, JSON_PRETTY_PRINT)
        );

        try {
            $job = $importService->process(Auth::id(), array_merge($validated, [
                'establishment_map' => $map,
            ]));

            return response()->json([
                'message' => 'Importación iniciada.',
                'job_id' => $job->id,
            ], 202);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function status($id)
    {
        $job = Facturador3ImportJob::find($id);

        if (! $job) {
            return response()->json(['message' => 'Job no encontrado.'], 404);
        }

        return response()->json([
            'id' => $job->id,
            'phase' => $job->phase,
            'status' => $job->status,
            'progress' => $job->progress,
            'processed_count' => $job->processed_count,
            'total_count' => $job->total_count,
            'error_message' => $job->error_message,
            'completed_at' => $job->completed_at,
        ]);
    }
}
