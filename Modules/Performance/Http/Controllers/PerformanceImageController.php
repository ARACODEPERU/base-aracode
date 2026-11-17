<?php

namespace Modules\Performance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Performance\Entities\PerformanceAlert;
use Modules\Performance\Services\ImageInventoryService;
use Modules\Performance\Services\ImageResizerService;
use Modules\Performance\Services\ImageToolsService;
use Modules\Performance\Services\PageWeightService;

class PerformanceImageController extends Controller
{
    public function __construct(
        private ImageInventoryService $inventory,
        private ImageResizerService $resizer,
        private ImageToolsService $tools,
        private PageWeightService $weights
    ) {
    }

    /**
     * Inventario de imágenes paginado y filtrado por pestaña.
     */
    public function index(Request $request)
    {
        $this->ensurePermission($request, 'performance_images');

        $quarantined = $this->inventory->quarantined();
        $all = $this->inventory->all($request->boolean('refresh'));
        $summary = $this->inventory->summary($all, $quarantined);

        $tab = (string) $request->get('tab', 'paginas');
        $search = trim((string) $request->get('search', ''));
        $minKb = max(0, (int) $request->get('min_kb', 0));
        $sort = in_array($request->get('sort'), ['bytes', 'name', 'width', 'date'], true)
            ? (string) $request->get('sort')
            : 'bytes';

        if ($tab === 'cuarentena') {
            $filtered = array_values(array_filter($quarantined, function (array $image) use ($search): bool {
                return $search === '' || str_contains(mb_strtolower($image['name'] . ' ' . $image['path']), mb_strtolower($search));
            }));
        } else {
            $filtered = $this->inventory->filter($all, $tab, $search, $minKb);
        }

        usort($filtered, function (array $a, array $b) use ($sort): int {
            return match ($sort) {
                'name'  => strcmp($a['name'], $b['name']),
                'width' => ((int) ($b['width'] ?? 0)) <=> ((int) ($a['width'] ?? 0)),
                'date'  => strcmp((string) $b['modified'], (string) $a['modified']),
                default => $b['bytes'] <=> $a['bytes'],
            };
        });

        return Inertia::render('Performance::Images/Index', [
            'images'  => $this->inventory->paginate(
                $filtered,
                (int) $request->get('page', 1),
                (int) config('performance.page_size', 20)
            ),
            'summary' => $summary,
            'tabs'    => $summary['tabs'],
            'filters' => [
                'tab'    => $tab,
                'search' => $search,
                'min_kb' => $minKb,
                'sort'   => $sort,
            ],
            'presets' => [1920, 1600, 1280, 800, 640],
            'alerts'  => PerformanceAlert::query()
                ->whereNull('reviewed_at')
                ->orderByDesc('detected_at')
                ->limit(20)
                ->get()
                ->map(fn (PerformanceAlert $alert): array => [
                    'id'         => $alert->id,
                    'path'       => $alert->path,
                    'name'       => basename($alert->path),
                    'bytes'      => (int) $alert->bytes,
                    'reason'     => $alert->reason,
                    'detected_at' => $alert->detected_at?->format('Y-m-d H:i'),
                ])
                ->all(),
            'limits'  => [
                'backup_days' => (int) config('performance.backup_days', 30),
                'new_days'    => (int) config('performance.new_days', 7),
                'webp_widths' => (array) config('performance.webp_widths', [0, 1280, 800]),
                'gif_supported' => $this->tools->supportsAnimatedGif(),
            ],
        ]);
    }

    /**
     * Redimensiona o recomprime una o varias imágenes.
     */
    public function optimize(Request $request)
    {
        $this->ensurePermission($request, 'performance_images');

        $data = $request->validate([
            'items'   => 'required|array|min:1|max:200',
            'items.*' => 'string',
            'mode'    => 'required|in:resize,compress',
            'width'   => 'nullable|integer|min:16|max:8000',
            'height'  => 'nullable|integer|min:16|max:8000',
            'quality' => 'nullable|integer|min:30|max:100',
        ]);

        if ($data['mode'] === 'resize' && empty($data['width']) && empty($data['height'])) {
            return response()->json([
                'success' => false,
                'message' => 'Indica un ancho o un alto máximo.',
            ], 422);
        }

        $quality = (int) ($data['quality'] ?? 82);

        $results = $this->runPerItem($data['items'], function (string $item) use ($data, $quality): array {
            if ($data['mode'] === 'compress') {
                return $this->tools->compress($item, $quality);
            }

            return $this->resizer->resize($item, $data['width'] ?? null, $data['height'] ?? null, $quality);
        });

        return $this->inventoryResponse($results);
    }

    /**
     * Genera variantes WebP sin tocar el original.
     */
    public function webp(Request $request)
    {
        $this->ensurePermission($request, 'performance_images');

        $data = $request->validate([
            'items'   => 'required|array|min:1|max:200',
            'items.*' => 'string',
            'quality' => 'nullable|integer|min:30|max:100',
        ]);

        $widths = (array) config('performance.webp_widths', [0, 1280, 800]);

        $results = $this->runPerItem(
            $data['items'],
            fn (string $item): array => $this->tools->webp($item, $widths, (int) ($data['quality'] ?? 82))
        );

        return $this->inventoryResponse($results);
    }

    /**
     * Convierte un GIF animado (solo disponible con Imagick).
     */
    public function convertGif(Request $request)
    {
        $this->ensurePermission($request, 'performance_images');

        $data = $request->validate([
            'items'   => 'required|array|min:1|max:50',
            'items.*' => 'string',
            'width'   => 'nullable|integer|min:16|max:4000',
            'quality' => 'nullable|integer|min:30|max:100',
        ]);

        $results = $this->runPerItem(
            $data['items'],
            fn (string $item): array => $this->tools->convertAnimatedGif(
                $item,
                isset($data['width']) ? (int) $data['width'] : null,
                (int) ($data['quality'] ?? 82)
            )
        );

        return $this->inventoryResponse($results);
    }

    /**
     * Mueve imágenes sin referencia a cuarentena.
     */
    public function quarantine(Request $request)
    {
        $this->ensurePermission($request, 'performance_images');

        $data = $request->validate([
            'items'   => 'required|array|min:1|max:200',
            'items.*' => 'string',
        ]);

        $results = $this->runPerItem($data['items'], fn (string $item): array => $this->tools->quarantine($item));

        return $this->inventoryResponse($results);
    }

    /**
     * Devuelve imágenes de cuarentena a su ruta original.
     */
    public function restoreQuarantine(Request $request)
    {
        $this->ensurePermission($request, 'performance_images');

        $data = $request->validate([
            'items'   => 'required|array|min:1|max:200',
            'items.*' => 'string',
        ]);

        $results = $this->runPerItem(
            $data['items'],
            fn (string $item): array => $this->tools->restoreQuarantine($item)
        );

        return $this->inventoryResponse($results);
    }

    /**
     * Restaura la copia de seguridad más reciente.
     */
    public function restore(Request $request)
    {
        $this->ensurePermission($request, 'performance_images');

        $data = $request->validate([
            'items'   => 'required|array|min:1|max:200',
            'items.*' => 'string',
        ]);

        $results = $this->runPerItem($data['items'], fn (string $item): array => $this->tools->restore($item));

        return $this->inventoryResponse($results);
    }

    /**
     * Marca las alertas como revisadas.
     */
    public function reviewAlerts(Request $request)
    {
        $this->ensurePermission($request, 'performance_images');

        PerformanceAlert::query()->whereNull('reviewed_at')->update(['reviewed_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Peso estimado por página.
     */
    public function weight(Request $request)
    {
        $this->ensurePermission($request, 'performance_weight');

        $all = $this->inventory->all($request->boolean('refresh'));
        $data = $this->weights->build($all);

        return Inertia::render('Performance::Pages/Weight', [
            'pages'  => $data['pages'],
            'budget' => $data['budget_bytes'],
            'globalAssets' => $data['global_assets'],
            'globalAssetsBytes' => $data['global_assets_bytes'],
            'totalBytes' => $data['total_bytes'],
            'overBudget' => $data['over_budget'],
        ]);
    }

    /**
     * Ejecuta una acción por imagen y devuelve el resultado individual.
     *
     * @param  array<int, string>  $items
     * @return array<int, array<string, mixed>>
     */
    private function runPerItem(array $items, callable $action): array
    {
        $results = [];

        foreach (array_unique($items) as $item) {
            try {
                $results[] = ['path' => $item] + $action((string) $item);
            } catch (\Throwable $e) {
                $results[] = [
                    'path'    => $item,
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     */
    private function inventoryResponse(array $results)
    {
        $this->inventory->forget();

        $quarantined = $this->inventory->quarantined();
        $all = $this->inventory->all();
        $summary = $this->inventory->summary($all, $quarantined);

        return response()->json([
            'success' => true,
            'results' => $results,
            'summary' => $summary,
        ]);
    }

    private function ensurePermission(Request $request, string $permission): void
    {
        $user = $request->user();

        abort_unless(
            $user && $user->hasAnyRole(['admin', 'Administrador']) && $user->hasPermissionTo($permission),
            403,
            'No tienes permiso para gestionar el rendimiento.'
        );
    }
}
