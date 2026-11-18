<?php

namespace Modules\Performance\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\Performance\Entities\PerformanceImage;

/**
 * Inventario de imágenes del sistema.
 *
 * Recorre las carpetas configuradas, lee tamaño, dimensiones, fecha y hash, y
 * cruza cada archivo con el índice de referencias y con el estado guardado en
 * base de datos (optimización, copia, WebP y cuarentena). El resultado se
 * cachea porque el escaneo recorre más de mil archivos.
 */
class ImageInventoryService
{
    public function __construct(private ImageReferenceResolver $references)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(bool $refresh = false): array
    {
        $key = 'performance.images.v2';

        if ($refresh) {
            Cache::forget($key);
        }

        return Cache::remember(
            $key,
            now()->addSeconds((int) config('performance.cache_seconds', 600)),
            fn (): array => $this->build()
        );
    }

    public function forget(): void
    {
        Cache::forget('performance.images.v2');
    }

    /**
     * Imágenes movidas a cuarentena: ya no están en disco público, así que se
     * listan desde su registro para poder restaurarlas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function quarantined(): array
    {
        return PerformanceImage::query()
            ->whereNotNull('quarantined_at')
            ->orderByDesc('quarantined_at')
            ->get()
            ->map(fn (PerformanceImage $state): array => [
                'id'               => $state->path,
                'name'             => basename($state->path),
                'path'             => $state->path,
                // El archivo ya no está en public/: se sirve con permiso desde
                // su copia en cuarentena para poder verlo y previsualizarlo.
                'url'              => route('performance_images_quarantine_file', ['path' => $state->path]),
                'folder'           => Str::after($state->path, 'public/'),
                'bytes'            => (int) $state->bytes,
                'width'            => $state->width,
                'height'           => $state->height,
                'type'             => strtolower(pathinfo($state->path, PATHINFO_EXTENSION)),
                'modified'         => $state->quarantined_at?->format('Y-m-d H:i'),
                'resizable'        => false,
                'resizable_reason' => 'En cuarentena: restáurala para poder procesarla',
                'default_tab'      => 'cuarentena',
                'references'       => [],
                'tab'              => 'cuarentena',
                'duplicate_count'  => 1,
                'hash'             => $state->hash,
                'is_new'           => false,
                'has_backup'       => (bool) $state->backup_path,
                'webp_url'         => null,
                'optimized_at'     => null,
                'quarantined'      => true,
                'quarantine_path'  => $state->quarantine_path,
                'gif_animated'     => false,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function build(): array
    {
        $images = $this->scan();
        $names = array_map(fn (array $image): string => strtolower($image['name']), $images);
        $references = $this->references->build($names);

        $hashes = array_filter(array_column($images, 'hash'));
        $hashCounts = array_count_values($hashes);

        foreach ($images as &$image) {
            $image['references'] = $references[strtolower($image['name'])] ?? [];
            $image['tab'] = $this->resolveTab($image);
            $image['duplicate_count'] = $image['hash'] ? ($hashCounts[$image['hash']] ?? 1) : 1;
        }

        unset($image);

        usort($images, fn (array $a, array $b): int => $b['bytes'] <=> $a['bytes']);

        return $images;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function scan(): array
    {
        $images = [];
        $extensions = (array) config('performance.extensions', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
        $ignored = (array) config('performance.ignore_directories', []);
        $newLimit = now()->subDays((int) config('performance.new_days', 7))->getTimestamp();

        $states = PerformanceImage::query()
            ->whereNull('quarantined_at')
            ->get()
            ->keyBy('path');

        foreach ((array) config('performance.directories', []) as $definition) {
            $directory = isset($definition['path'])
                ? str_replace('\\', '/', rtrim($definition['path'], '\\/'))
                : null;

            if (! $directory || ! is_dir($directory)) {
                continue;
            }

            foreach (File::allFiles($directory) as $file) {
                $extension = strtolower($file->getExtension());

                if (! in_array($extension, $extensions, true)) {
                    continue;
                }

                $absolute = str_replace('\\', '/', $file->getPathname());

                if ($this->isIgnored($absolute, $ignored)) {
                    continue;
                }

                $relative = $this->relativePath($absolute);
                $inside = ltrim(Str::after($absolute, $directory), '/');
                $url = rtrim($definition['url'] ?? '', '/') . '/' . $this->encodePath($inside);
                $dimensions = @getimagesize($absolute);
                $bytes = (int) (@filesize($absolute) ?: 0);
                $gifAnimated = $extension === 'gif' && GifInspector::isAnimated($absolute);
                $state = $states->get($relative);
                $modified = (int) @filemtime($absolute);
                $webpAbsolute = preg_replace('/\.[a-z0-9]+$/i', '.webp', $absolute);
                $hasWebp = is_file($webpAbsolute);

                $images[] = [
                    'id'               => $relative,
                    'name'             => $file->getFilename(),
                    'path'             => $relative,
                    'url'              => $url,
                    'folder'           => Str::after($relative, 'public/'),
                    'bytes'            => $bytes,
                    'width'            => $dimensions[0] ?? null,
                    'height'           => $dimensions[1] ?? null,
                    'type'             => $extension,
                    'modified'         => date('Y-m-d H:i', $modified),
                    'resizable'        => $dimensions !== false && ! $gifAnimated,
                    'resizable_reason' => $gifAnimated
                        ? 'GIF animado: GD perdería la animación'
                        : ($dimensions === false ? 'No se pudieron leer las dimensiones' : null),
                    'default_tab'      => $definition['tab'] ?? 'otros',
                    'references'       => [],
                    'tab'              => 'otros',
                    'hash'             => @sha1_file($absolute) ?: null,
                    'duplicate_count'  => 1,
                    'is_new'           => $modified >= $newLimit,
                    'has_backup'       => (bool) $state?->backup_path,
                    'optimized_at'     => $state?->optimized_at?->format('Y-m-d H:i'),
                    'compressed_bytes' => $state?->compressed_bytes,
                    'webp_url'         => $hasWebp ? $this->publicUrl($this->relativePath($webpAbsolute)) : null,
                    'gif_animated'     => $gifAnimated,
                    'quarantined'      => false,
                ];
            }
        }

        return $images;
    }

    /**
     * @param  array<string, mixed>  $image
     */
    private function resolveTab(array $image): string
    {
        $kinds = array_column($image['references'], 'kind');

        if (in_array('curso', $kinds, true)) {
            return 'cursos';
        }

        if (in_array('blog', $kinds, true)) {
            return 'blog';
        }

        if (in_array('producto', $kinds, true)) {
            return 'productos';
        }

        $path = strtolower($image['path']);

        if (str_contains($path, '/uploads/courses') || str_contains($path, '/courses/')) {
            return 'cursos';
        }

        if (str_contains($path, '/uploads/blog') || str_contains($path, '/articles/')) {
            return 'blog';
        }

        if (str_contains($path, '/uploads/items') || str_contains($path, '/products/')) {
            return 'productos';
        }

        return $image['default_tab'] ?? 'otros';
    }

    /**
     * @param  array<int, array<string, mixed>>  $images
     * @param  array<int, array<string, mixed>>  $quarantined
     * @return array<string, mixed>
     */
    public function summary(array $images, array $quarantined = []): array
    {
        $threshold = ((int) config('performance.oversized_kb', 300)) * 1024;
        $tabs = [];

        foreach ((array) config('performance.tabs', []) as $key => $label) {
            $tabs[$key] = ['key' => $key, 'label' => $label, 'count' => 0, 'bytes' => 0];
        }

        $bytes = 0;
        $oversized = 0;
        $orphans = 0;
        $duplicatesExtra = 0;
        $seenHashes = [];
        $gifBytes = 0;

        foreach ($images as $image) {
            $bytes += $image['bytes'];

            if ($image['bytes'] >= $threshold) {
                $oversized++;
            }

            $key = $image['tab'];

            if (! isset($tabs[$key])) {
                $tabs[$key] = ['key' => $key, 'label' => ucfirst($key), 'count' => 0, 'bytes' => 0];
            }

            $tabs[$key]['count']++;
            $tabs[$key]['bytes'] += $image['bytes'];

            if ($image['references'] === []) {
                $orphans++;
                $tabs['orfanas']['count']++;
                $tabs['orfanas']['bytes'] += $image['bytes'];
            }

            if ($image['duplicate_count'] > 1 && $image['hash']) {
                // Solo se cuenta como desperdicio la copia extra: la primera
                // de cada hash es la que se conservaría.
                if (isset($seenHashes[$image['hash']])) {
                    $duplicatesExtra += $image['bytes'];
                    $tabs['duplicadas']['count']++;
                    $tabs['duplicadas']['bytes'] += $image['bytes'];
                } else {
                    $seenHashes[$image['hash']] = true;
                    $tabs['duplicadas']['count']++;
                    $tabs['duplicadas']['bytes'] += $image['bytes'];
                }
            }

            if ($image['gif_animated']) {
                $gifBytes += $image['bytes'];
                $tabs['gif']['count']++;
                $tabs['gif']['bytes'] += $image['bytes'];
            }
        }

        foreach ($quarantined as $image) {
            $tabs['cuarentena']['count']++;
            $tabs['cuarentena']['bytes'] += $image['bytes'];
        }

        return [
            'total'        => count($images),
            'bytes'        => $bytes,
            'oversized'    => $oversized,
            'threshold_kb' => (int) config('performance.oversized_kb', 300),
            'orphans'      => $orphans,
            'tabs'         => array_values($tabs),
            'savings'      => [
                'duplicates' => $duplicatesExtra,
                'orphans'    => (int) array_sum(array_map(
                    fn (array $image): int => $image['references'] === [] ? $image['bytes'] : 0,
                    $images
                )),
                'gif'        => $gifBytes,
            ],
        ];
    }

    /**
     * Filtra por pestaña, texto y peso mínimo.
     *
     * @param  array<int, array<string, mixed>>  $images
     * @return array<int, array<string, mixed>>
     */
    public function filter(array $images, string $tab, string $search, int $minKb): array
    {
        return array_values(array_filter($images, function (array $image) use ($tab, $search, $minKb): bool {
            if ($tab === 'orfanas' && $image['references'] !== []) {
                return false;
            }

            if ($tab === 'duplicadas' && (int) $image['duplicate_count'] < 2) {
                return false;
            }

            if ($tab === 'gif' && ! $image['gif_animated']) {
                return false;
            }

            if (! in_array($tab, ['todos', 'orfanas', 'duplicadas', 'gif'], true) && $image['tab'] !== $tab) {
                return false;
            }

            if ($minKb > 0 && $image['bytes'] < $minKb * 1024) {
                return false;
            }

            if ($search !== '') {
                $haystack = mb_strtolower(
                    $image['name'] . ' ' . $image['path'] . ' '
                    . implode(' ', array_column($image['references'], 'label'))
                );

                if (! str_contains($haystack, mb_strtolower($search))) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * @param  array<int, array<string, mixed>>  $images
     * @return array<string, mixed>
     */
    public function paginate(array $images, int $page, int $perPage): array
    {
        $total = count($images);
        $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
        $page = min(max(1, $page), $lastPage);

        return [
            'data'      => array_slice($images, ($page - 1) * $perPage, $perPage),
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => $lastPage,
        ];
    }

    /**
     * @param  array<int, string>  $ignored
     */
    private function isIgnored(string $absolute, array $ignored): bool
    {
        foreach ($ignored as $segment) {
            if (str_contains($absolute, '/' . $segment . '/')) {
                return true;
            }
        }

        return false;
    }

    public function publicUrl(string $relativePath): string
    {
        if (str_starts_with($relativePath, 'public/')) {
            $path = Str::after($relativePath, 'public/');
        } elseif (str_starts_with($relativePath, 'storage/app/public/')) {
            $path = 'storage/' . Str::after($relativePath, 'storage/app/public/');
        } else {
            $path = ltrim($relativePath, '/');
        }

        return '/' . $this->encodePath($path);
    }

    private function encodePath(string $path): string
    {
        return implode('/', array_map(
            fn (string $segment): string => rawurlencode(rawurldecode($segment)),
            explode('/', $path)
        ));
    }

    private function relativePath(string $absolute): string
    {
        $base = str_replace('\\', '/', base_path()) . '/';
        $path = str_replace('\\', '/', $absolute);

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }
}
