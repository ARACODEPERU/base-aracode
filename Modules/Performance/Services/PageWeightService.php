<?php

namespace Modules\Performance\Services;

/**
 * Peso estimado de cada página.
 *
 * Usa las referencias del inventario: para cada página suma las imágenes que
 * aparecen en ella (una sola vez por archivo) y, en las páginas públicas, los
 * recursos globales configurados (CSS y JS compartidos). No inventa cifras:
 * si una vista no tiene ruta conocida se agrupa por su nombre.
 */
class PageWeightService
{
    /**
     * @param  array<int, array<string, mixed>>  $images
     * @return array<string, mixed>
     */
    public function build(array $images): array
    {
        $budget = ((int) config('performance.page_budget_kb', 1500)) * 1024;
        $globalAssets = $this->globalAssets();
        $pages = [];

        foreach ($images as $image) {
            $seen = [];

            foreach ($image['references'] as $reference) {
                // Solo páginas: las hojas de estilo son recursos globales y los
                // ítems sin ruta pública no representan una página navegable.
                if ($reference['kind'] === 'css') {
                    continue;
                }

                if (! $reference['url'] && $reference['kind'] !== 'vista') {
                    continue;
                }

                $key = $reference['url'] ?: 'vista:' . $reference['label'];

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;

                $pages[$key] ??= [
                    'key'        => $key,
                    'label'      => $reference['label'],
                    'url'        => $reference['url'],
                    'bytes'      => 0,
                    'assets'     => $reference['url'] ? $globalAssets['bytes'] : 0,
                    'images'     => 0,
                    'heaviest'   => [],
                ];

                $pages[$key]['bytes'] += $image['bytes'];
                $pages[$key]['images']++;
                $pages[$key]['heaviest'][] = [
                    'name'   => $image['name'],
                    'bytes'  => $image['bytes'],
                    'width'  => $image['width'],
                    'height' => $image['height'],
                ];
            }
        }

        foreach ($pages as &$page) {
            usort($page['heaviest'], fn (array $a, array $b): int => $b['bytes'] <=> $a['bytes']);
            $page['heaviest'] = array_slice($page['heaviest'], 0, 3);
            $page['total_bytes'] = $page['bytes'] + $page['assets'];
            $page['over_budget'] = $page['total_bytes'] > $budget;
            $page['percent'] = $budget > 0 ? min(100, (int) round($page['total_bytes'] / $budget * 100)) : 0;
        }

        unset($page);

        $pages = array_values($pages);
        usort($pages, fn (array $a, array $b): int => $b['total_bytes'] <=> $a['total_bytes']);

        return [
            'pages'              => $pages,
            'budget_bytes'       => $budget,
            'global_assets'      => $globalAssets['files'],
            'global_assets_bytes' => $globalAssets['bytes'],
            'total_bytes'        => array_sum(array_column($pages, 'total_bytes')),
            'over_budget'        => count(array_filter($pages, fn (array $page): bool => $page['over_budget'])),
        ];
    }

    /**
     * @return array{bytes: int, files: array<int, array<string, mixed>>}
     */
    private function globalAssets(): array
    {
        $bytes = 0;
        $files = [];

        foreach ((array) config('performance.global_assets', []) as $asset) {
            $absolute = public_path($asset);

            if (! is_file($absolute)) {
                continue;
            }

            $size = (int) filesize($absolute);
            $bytes += $size;
            $files[] = ['path' => $asset, 'bytes' => $size];
        }

        return ['bytes' => $bytes, 'files' => $files];
    }
}
