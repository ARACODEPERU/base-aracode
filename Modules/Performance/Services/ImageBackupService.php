<?php

namespace Modules\Performance\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Copias de seguridad de las imágenes antes de modificarlas.
 *
 * Cada copia vive en storage/app/performance-backups/{fecha}-{motivo}-{aleatorio}
 * conservando la ruta relativa original, así que restaurar es volver a copiar
 * el mismo archivo. Nunca se sobrescribe una copia anterior.
 */
class ImageBackupService
{
    public function directory(): string
    {
        return storage_path('app/performance-backups');
    }

    /**
     * Copia el archivo y devuelve su ruta relativa al proyecto.
     */
    public function backup(string $relativePath, string $reason = 'cambio'): ?string
    {
        $source = base_path($relativePath);

        if (! is_file($source)) {
            return null;
        }

        $reason = Str::slug($reason) ?: 'cambio';
        // La carpeta lleva fecha con milisegundos: el orden de las copias se
        // deduce del nombre, no de filemtime (varias copias caen en el mismo
        // segundo y filemtime no las distingue).
        $target = $this->directory()
            . '/' . now()->format('YmdHisv') . '-' . $reason . '-' . bin2hex(random_bytes(3))
            . '/' . ltrim($relativePath, '/');

        File::ensureDirectoryExists(dirname($target));

        if (! File::copy($source, $target)) {
            throw new RuntimeException('No se pudo guardar la copia de seguridad.');
        }

        return $this->relativePath($target);
    }

    /**
     * Copia más reciente de un archivo, o null si nunca se respaldó.
     */
    public function latest(string $relativePath): ?string
    {
        return $this->copies($relativePath)[0] ?? null;
    }

    /**
     * Copia más antigua: el estado original antes de cualquier cambio.
     */
    public function oldest(string $relativePath): ?string
    {
        $copies = $this->copies($relativePath);

        return $copies ? end($copies) : null;
    }

    /**
     * Copias del archivo, de la más reciente a la más antigua.
     *
     * @return array<int, string>
     */
    private function copies(string $relativePath): array
    {
        $files = glob($this->directory() . '/*/' . ltrim($relativePath, '/'));

        if (! $files) {
            return [];
        }

        sort($files, SORT_STRING);

        return array_map(
            fn (string $file): string => $this->relativePath($file),
            array_reverse($files)
        );
    }

    /**
     * Restaura el original (la copia más antigua) sobre el archivo actual.
     *
     * @return array{backup: string, bytes: int}
     */
    public function restore(string $relativePath): array
    {
        $backup = $this->oldest($relativePath);

        if (! $backup) {
            throw new RuntimeException('Esta imagen no tiene copia de seguridad.');
        }

        $absolute = base_path($relativePath);

        // El estado actual también se respalda: deshacer una restauración es
        // posible desde la misma pantalla.
        $this->backup($relativePath, 'antes-de-restaurar');

        File::ensureDirectoryExists(dirname($absolute));

        if (! File::copy(base_path($backup), $absolute)) {
            throw new RuntimeException('No se pudo restaurar la copia de seguridad.');
        }

        return [
            'backup' => $backup,
            'bytes'  => (int) @filesize($absolute),
        ];
    }

    /**
     * Uso actual del disco de copias y cuánto de él ya superó la retención.
     *
     * @return array{files: int, bytes: int, folders: int, expired_files: int, expired_bytes: int, oldest: string|null, retention_days: int}
     */
    public function summary(int $days): array
    {
        $summary = [
            'files'           => 0,
            'bytes'           => 0,
            'folders'         => 0,
            'expired_files'   => 0,
            'expired_bytes'   => 0,
            'oldest'          => null,
            'retention_days'  => max(1, $days),
        ];

        if (! is_dir($this->directory())) {
            return $summary;
        }

        $limit = now()->subDays(max(1, $days))->getTimestamp();

        foreach (glob($this->directory() . '/*', GLOB_ONLYDIR) ?: [] as $folder) {
            $summary['folders']++;
            $modified = (int) filemtime($folder);
            $expired = $modified < $limit;

            if ($summary['oldest'] === null || $modified < strtotime($summary['oldest'])) {
                $summary['oldest'] = date('Y-m-d', $modified);
            }

            foreach (File::allFiles($folder) as $file) {
                $size = (int) $file->getSize();
                $summary['files']++;
                $summary['bytes'] += $size;

                if ($expired) {
                    $summary['expired_files']++;
                    $summary['expired_bytes'] += $size;
                }
            }
        }

        return $summary;
    }

    /**
     * Borra las copias más antiguas que la retención indicada y devuelve
     * cuántos archivos y bytes se liberaron.
     *
     * @return array{folders: int, files: int, bytes: int}
     */
    public function purgeExpired(int $days): array
    {
        $purged = ['folders' => 0, 'files' => 0, 'bytes' => 0];

        if ($days <= 0 || ! is_dir($this->directory())) {
            return $purged;
        }

        $limit = now()->subDays($days)->getTimestamp();

        foreach (glob($this->directory() . '/*', GLOB_ONLYDIR) ?: [] as $folder) {
            if (filemtime($folder) >= $limit) {
                continue;
            }

            foreach (File::allFiles($folder) as $file) {
                $purged['files']++;
                $purged['bytes'] += (int) $file->getSize();
            }

            File::deleteDirectory($folder);
            $purged['folders']++;
        }

        return $purged;
    }

    /**
     * Borra las copias más antiguas que la retención indicada.
     */
    public function cleanup(int $days): int
    {
        return $this->purgeExpired($days)['folders'];
    }

    public function relativePath(string $absolute): string
    {
        $base = str_replace('\\', '/', base_path()) . '/';
        $path = str_replace('\\', '/', $absolute);

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }
}
