<?php

namespace Modules\Performance\Services;

use Modules\Performance\Entities\PerformanceImage;
use RuntimeException;

/**
 * Redimensiona imágenes con GD conservando el nombre y el formato.
 *
 * Escribe el resultado en un archivo temporal y reemplaza el original solo si
 * salió bien, para no dejarlo a medias si falla el guardado. Nunca amplía: si
 * la imagen ya es menor que el destino, no se toca. Antes de reemplazar guarda
 * una copia de seguridad para poder deshacer.
 */
class ImageResizerService
{
    public function __construct(private ImageBackupService $backups)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function resize(string $relativePath, ?int $width, ?int $height, int $quality = 82): array
    {
        $relativePath = str_replace('\\', '/', $relativePath);

        if (! $this->isAllowed($relativePath)) {
            throw new RuntimeException('La ruta de la imagen no es válida.');
        }

        $absolute = base_path($relativePath);

        if (! is_file($absolute)) {
            throw new RuntimeException('La imagen no existe en el servidor.');
        }

        $extension = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));

        if ($extension === 'gif' && GifInspector::isAnimated($absolute)) {
            return [
                'status'  => 'skipped',
                'message' => 'GIF animado: no se redimensiona para no perder la animación.',
            ];
        }

        if (! in_array($extension, GdImageSupport::extensions(), true)) {
            return [
                'status'  => 'skipped',
                'message' => 'Formato de imagen no soportado.',
            ];
        }

        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('La extensión GD de PHP no está habilitada.');
        }

        $info = @getimagesize($absolute);

        if ($info === false || (int) $info[0] < 1 || (int) $info[1] < 1) {
            return [
                'status'  => 'skipped',
                'message' => 'No se pudieron leer las dimensiones de la imagen.',
            ];
        }

        $originalWidth = (int) $info[0];
        $originalHeight = (int) $info[1];
        $originalBytes = (int) @filesize($absolute);

        $target = $this->targetSize($originalWidth, $originalHeight, $width, $height);

        if ($target === null) {
            return [
                'status'  => 'skipped',
                'message' => 'La imagen ya es igual o más pequeña que el tamaño pedido.',
            ];
        }

        [$newWidth, $newHeight] = $target;

        $source = GdImageSupport::open($absolute, $extension);

        if (! $source) {
            throw new RuntimeException('No se pudo abrir la imagen.');
        }

        $destination = GdImageSupport::canvas($newWidth, $newHeight, $extension);

        imagecopyresampled(
            $destination,
            $source,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $originalWidth,
            $originalHeight
        );

        $temporary = $absolute . '.perf-' . bin2hex(random_bytes(6)) . '.' . $extension;
        $saved = GdImageSupport::save($destination, $temporary, $extension, $quality);

        if (! $saved) {
            @unlink($temporary);

            throw new RuntimeException('No se pudo guardar la imagen redimensionada.');
        }

        $newBytes = (int) @filesize($temporary);

        // Si no se reduce el peso, se deja el original intacto.
        if ($newBytes >= $originalBytes) {
            @unlink($temporary);

            return [
                'status'  => 'unchanged',
                'message' => 'El archivo resultante no era más liviano; se dejó el original.',
                'before'  => $this->data($originalBytes, $originalWidth, $originalHeight),
                'after'   => $this->data($newBytes, $newWidth, $newHeight),
            ];
        }

        $backup = $this->backups->backup($relativePath, 'redimensionar');
        $this->replace($temporary, $absolute);

        PerformanceImage::updateOrCreate(
            ['path' => $relativePath],
            [
                'bytes'        => $newBytes,
                'width'        => $newWidth,
                'height'       => $newHeight,
                'optimized_at' => now(),
                'backup_path'  => $backup,
            ]
        );

        return [
            'status' => 'resized',
            'before' => $this->data($originalBytes, $originalWidth, $originalHeight),
            'after'  => $this->data($newBytes, $newWidth, $newHeight),
            'backup' => $backup,
        ];
    }

    /**
     * Calcula el tamaño destino conservando la proporción. Devuelve null si no
     * hay nada que reducir.
     *
     * @return array{0: int, 1: int}|null
     */
    private function targetSize(int $width, int $height, ?int $maxWidth, ?int $maxHeight): ?array
    {
        if (! $maxWidth && ! $maxHeight) {
            throw new RuntimeException('Indica un ancho o un alto máximo.');
        }

        $ratio = PHP_FLOAT_MAX;

        if ($maxWidth) {
            $ratio = min($ratio, $maxWidth / $width);
        }

        if ($maxHeight) {
            $ratio = min($ratio, $maxHeight / $height);
        }

        if ($ratio >= 1) {
            return null;
        }

        return [max(1, (int) round($width * $ratio)), max(1, (int) round($height * $ratio))];
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

            throw new RuntimeException('No se pudo colocar la imagen redimensionada.');
        }

        @unlink($backup);
    }

    /**
     * @return array{bytes: int, width: int, height: int}
     */
    private function data(int $bytes, int $width, int $height): array
    {
        return ['bytes' => $bytes, 'width' => $width, 'height' => $height];
    }

    private function isAllowed(string $relativePath): bool
    {
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return false;
        }

        if (! preg_match('#^(public|storage/app/public)/#', $relativePath)) {
            return false;
        }

        foreach ((array) config('performance.directories', []) as $definition) {
            $directory = $definition['path'] ?? null;

            if (! $directory) {
                continue;
            }

            $base = $this->relativePath($directory);

            if (str_starts_with($relativePath, rtrim($base, '/') . '/')) {
                return true;
            }
        }

        return false;
    }

    private function relativePath(string $absolute): string
    {
        $base = str_replace('\\', '/', base_path()) . '/';
        $path = str_replace('\\', '/', $absolute);

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }
}
