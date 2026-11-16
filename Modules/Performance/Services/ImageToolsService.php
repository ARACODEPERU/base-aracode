<?php

namespace Modules\Performance\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\Performance\Entities\PerformanceImage;
use RuntimeException;

/**
 * Acciones sobre una imagen ya existente: comprimirla sin tocar su resolución,
 * generar variantes WebP, moverla a cuarentena y restaurarla.
 *
 * Todo cambio destructivo pasa antes por ImageBackupService, así que la
 * pantalla puede deshacerlo. El original nunca cambia de nombre ni de formato
 * salvo en la conversión explícita de un GIF animado (que exige Imagick).
 */
class ImageToolsService
{
    public function __construct(private ImageBackupService $backups)
    {
    }

    /**
     * Recomprime sin cambiar las dimensiones.
     *
     * @return array<string, mixed>
     */
    public function compress(string $relativePath, int $quality = 82): array
    {
        $absolute = $this->validPath($relativePath);
        $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        $info = @getimagesize($absolute);

        if ($info === false) {
            throw new RuntimeException('No se pudieron leer las dimensiones de la imagen.');
        }

        if ($extension === 'gif' && GifInspector::isAnimated($absolute)) {
            return ['status' => 'skipped', 'message' => 'GIF animado: requiere Imagick para conservar la animación.'];
        }

        if (! in_array($extension, GdImageSupport::extensions(), true)) {
            return ['status' => 'skipped', 'message' => 'Formato no soportado por GD.'];
        }

        $originalBytes = (int) @filesize($absolute);
        $source = GdImageSupport::open($absolute, $extension);

        if (! $source) {
            throw new RuntimeException('No se pudo abrir la imagen.');
        }

        $temporary = $absolute . '.opt-' . bin2hex(random_bytes(6)) . '.' . $extension;
        $saved = GdImageSupport::save($source, $temporary, $extension, $quality);

        if (! $saved) {
            @unlink($temporary);

            throw new RuntimeException('No se pudo recomprimir la imagen.');
        }

        $newBytes = (int) @filesize($temporary);

        if ($newBytes >= $originalBytes) {
            @unlink($temporary);

            return [
                'status'  => 'unchanged',
                'message' => 'La recompresión no redujo el peso; se dejó el original.',
                'before'  => ['bytes' => $originalBytes, 'width' => $info[0], 'height' => $info[1]],
                'after'   => ['bytes' => $newBytes, 'width' => $info[0], 'height' => $info[1]],
            ];
        }

        $backup = $this->backups->backup($relativePath, 'comprimir');
        $this->replace($temporary, $absolute);
        $this->remember($relativePath, [
            'bytes'            => $newBytes,
            'width'            => $info[0],
            'height'           => $info[1],
            'optimized_at'     => now(),
            'compressed_bytes' => $newBytes,
            'backup_path'      => $backup,
        ]);

        return [
            'status' => 'resized',
            'before' => ['bytes' => $originalBytes, 'width' => $info[0], 'height' => $info[1]],
            'after'  => ['bytes' => $newBytes, 'width' => $info[0], 'height' => $info[1]],
            'backup' => $backup,
        ];
    }

    /**
     * Genera variantes WebP junto al original, sin modificarlo.
     *
     * @param  array<int, int>  $widths  0 significa «tamaño original».
     * @return array<string, mixed>
     */
    public function webp(string $relativePath, array $widths = [0, 1280, 800], int $quality = 82): array
    {
        $absolute = $this->validPath($relativePath);
        $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));

        if (! function_exists('imagewebp')) {
            throw new RuntimeException('La extensión GD no tiene soporte WebP.');
        }

        if ($extension === 'gif' && GifInspector::isAnimated($absolute)) {
            return ['status' => 'skipped', 'message' => 'GIF animado: requiere Imagick para conservar la animación.'];
        }

        $info = @getimagesize($absolute);

        if ($info === false) {
            throw new RuntimeException('No se pudieron leer las dimensiones de la imagen.');
        }

        $source = GdImageSupport::open($absolute, $extension);

        if (! $source) {
            throw new RuntimeException('No se pudo abrir la imagen.');
        }

        $originalWidth = (int) $info[0];
        $originalHeight = (int) $info[1];
        $files = [];

        foreach (array_unique($widths) as $width) {
            $width = (int) $width;
            $targetWidth = $width > 0 ? min($width, $originalWidth) : $originalWidth;
            $targetHeight = max(1, (int) round($originalHeight * ($targetWidth / $originalWidth)));

            $suffix = $width > 0 ? '-' . $targetWidth : '';
            $target = preg_replace('/\.[a-z0-9]+$/i', $suffix . '.webp', $absolute);

            if ($targetWidth === $originalWidth) {
                $canvas = GdImageSupport::canvas($targetWidth, $targetHeight, 'webp');
                imagecopy($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight);
            } else {
                $canvas = GdImageSupport::canvas($targetWidth, $targetHeight, 'webp');
                imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $originalWidth, $originalHeight);
            }

            if (! @imagewebp($canvas, $target, min(100, max(30, $quality)))) {
                throw new RuntimeException('No se pudo escribir la variante WebP.');
            }

            $relativeTarget = $this->backups->relativePath($target);

            $files[] = [
                'path'   => $relativeTarget,
                'url'    => $this->publicUrl($relativeTarget),
                'width'  => $targetWidth,
                'height' => $targetHeight,
                'bytes'  => (int) @filesize($target),
            ];
        }

        $state = $this->remember($relativePath, [
            'bytes'      => (int) @filesize($absolute),
            'width'      => $originalWidth,
            'height'     => $originalHeight,
            'webp_path'  => $files[0]['path'] ?? null,
            'webp_bytes' => $files[0]['bytes'] ?? null,
        ]);

        return [
            'status' => 'webp',
            'files'  => $files,
            'saved'  => max(0, (int) @filesize($absolute) - ($files[0]['bytes'] ?? 0)),
            'state'  => $state,
        ];
    }

    /**
     * Mueve la imagen fuera de la vista pública, conservando una copia.
     *
     * @return array<string, mixed>
     */
    public function quarantine(string $relativePath): array
    {
        $absolute = $this->validPath($relativePath);
        $backup = $this->backups->backup($relativePath, 'cuarentena');

        $target = storage_path('app/performance-quarantine/' . date('Ymd-His') . '/' . ltrim($relativePath, '/'));

        File::ensureDirectoryExists(dirname($target));

        if (! @rename($absolute, $target)) {
            if (! @copy($absolute, $target) || ! @unlink($absolute)) {
                throw new RuntimeException('No se pudo mover la imagen a cuarentena.');
            }
        }

        $this->remember($relativePath, [
            'quarantine_path' => $this->backups->relativePath($target),
            'quarantined_at'  => now(),
            'backup_path'     => $backup ?? PerformanceImage::where('path', $relativePath)->value('backup_path'),
        ]);

        return [
            'status'   => 'quarantined',
            'quarantine_path' => $this->backups->relativePath($target),
        ];
    }

    /**
     * Devuelve una imagen en cuarentena a su ruta original.
     *
     * @return array<string, mixed>
     */
    public function restoreQuarantine(string $relativePath): array
    {
        $state = PerformanceImage::query()->where('path', $relativePath)->first();

        if (! $state || ! $state->quarantine_path) {
            throw new RuntimeException('Esta imagen no está en cuarentena.');
        }

        $source = base_path($state->quarantine_path);

        if (! is_file($source)) {
            throw new RuntimeException('El archivo en cuarentena ya no existe.');
        }

        $target = base_path($relativePath);
        File::ensureDirectoryExists(dirname($target));

        if (! @copy($source, $target)) {
            throw new RuntimeException('No se pudo restaurar la imagen desde cuarentena.');
        }

        @unlink($source);

        $state->forceFill([
            'quarantine_path' => null,
            'quarantined_at'  => null,
            'bytes'           => (int) @filesize($target),
        ])->save();

        return ['status' => 'restored', 'path' => $relativePath];
    }

    /**
     * Restaura la copia de seguridad más reciente.
     *
     * @return array<string, mixed>
     */
    public function restore(string $relativePath): array
    {
        $result = $this->backups->restore($relativePath);

        PerformanceImage::query()->where('path', $relativePath)->update([
            'optimized_at'     => null,
            'compressed_bytes' => null,
            'webp_path'        => null,
            'webp_bytes'       => null,
        ]);

        clearstatcache(true, base_path($relativePath));

        return ['status' => 'restored'] + $result;
    }

    public function supportsAnimatedGif(): bool
    {
        return extension_loaded('imagick');
    }

    /**
     * Convierte un GIF animado a WebP animado (solo con Imagick).
     *
     * @return array<string, mixed>
     */
    public function convertAnimatedGif(string $relativePath, ?int $width = null, int $quality = 82): array
    {
        if (! $this->supportsAnimatedGif()) {
            return [
                'status'  => 'skipped',
                'message' => 'Para convertir GIF animados hace falta la extensión Imagick (o una herramienta externa como gifsicle).',
            ];
        }

        $absolute = $this->validPath($relativePath);

        $image = new \Imagick($absolute);
        $image = $image->coalesceImages();

        if ($width) {
            foreach ($image as $frame) {
                $frame->resizeImage($width, 0, \Imagick::FILTER_LANCZOS, 1);
            }
        }

        $image->setImageFormat('webp');
        $image->setImageCompressionQuality(min(100, max(30, $quality)));

        $target = preg_replace('/\.[a-z0-9]+$/i', '.webp', $absolute);
        $image->writeImages($target, true);
        $image->clear();

        $relativeTarget = $this->backups->relativePath($target);

        return [
            'status' => 'webp',
            'files'  => [[
                'path'  => $relativeTarget,
                'url'   => $this->publicUrl($relativeTarget),
                'bytes' => (int) @filesize($target),
            ]],
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function remember(string $relativePath, array $values): PerformanceImage
    {
        return PerformanceImage::updateOrCreate(
            ['path' => $relativePath],
            $values
        );
    }

    private function replace(string $temporary, string $absolute): void
    {
        $backup = $absolute . '.bak-' . bin2hex(random_bytes(4));

        if (! @rename($absolute, $backup)) {
            @unlink($temporary);

            throw new RuntimeException('No se pudo reemplazar la imagen original.');
        }

        if (! @rename($temporary, $absolute)) {
            @rename($backup, $absolute);
            @unlink($temporary);

            throw new RuntimeException('No se pudo colocar la imagen procesada.');
        }

        @unlink($backup);
    }

    private function validPath(string $relativePath): string
    {
        $relativePath = str_replace('\\', '/', $relativePath);
        $absolute = base_path($relativePath);

        if ($relativePath === '' || str_contains($relativePath, '..') || ! is_file($absolute)) {
            throw new RuntimeException('La imagen no existe o la ruta no es válida.');
        }

        if (! preg_match('#^(public|storage/app/public)/#', $relativePath)) {
            throw new RuntimeException('La ruta de la imagen no está permitida.');
        }

        foreach ((array) config('performance.directories', []) as $definition) {
            $directory = $definition['path'] ?? null;

            if (! $directory) {
                continue;
            }

            $base = $this->backups->relativePath($directory);

            if (str_starts_with($relativePath, rtrim($base, '/') . '/')) {
                return $absolute;
            }
        }

        throw new RuntimeException('La ruta de la imagen no está permitida.');
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

        $encoded = implode('/', array_map(
            fn (string $segment): string => rawurlencode(rawurldecode($segment)),
            explode('/', $path)
        ));

        return '/' . $encoded;
    }
}
