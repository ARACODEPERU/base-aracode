<?php

namespace Modules\Security\Services;

use App\Models\Parameter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class StorageMetricsService
{
    /** Clave de caché donde vive la última medición completa. */
    public const CACHE_KEY = 'security_storage_metrics';

    /** Minutos que dura la medición en caché (24 h). */
    public const CACHE_TTL_MINUTES = 1440;

    /** Código del parámetro que define la cuota de almacenamiento en GB (única fuente de capacidad). */
    public const QUOTA_PARAMETER = 'PHD0001';

    /**
     * Devuelve la medición actual (desde caché si existe).
     * Estructura: users{total,image,pdf,other}, system{total,logs,framework,vendor},
     * public_static{total}, database{total}, used, quota, used_percentage,
     * scanned_at.
     */
    public function get(): array
    {
        return Cache::get(self::CACHE_KEY) ?? $this->measure();
    }

    /**
     * Ejecuta una medición fresca y la guarda en caché.
     */
    public function measure(): array
    {
        $metrics = $this->calculate();
        Cache::put(self::CACHE_KEY, $metrics, now()->addMinutes(self::CACHE_TTL_MINUTES));

        return $metrics;
    }

    /**
     * Limpia la caché de la medición.
     */
    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Realiza el escaneo completo y calcula todas las métricas.
     */
    public function calculate(): array
    {
        $userMetrics = $this->scanDirectory(storage_path('app'));

        // public/ excluyendo public/storage (es un symlink a storage/app/public,
        // contarlo duplicaría los archivos de usuarios).
        $publicMetrics = $this->scanDirectory(public_path(), [
            public_path('storage'),
        ]);

        $logsSize = $this->directorySize(storage_path('logs'));
        $frameworkSize = $this->directorySize(storage_path('framework'));
        $vendorSize = $this->directorySize(base_path('vendor'));
        $systemTotal = $logsSize + $frameworkSize + $vendorSize;

        $databaseSize = $this->databaseSizeBytes();

        $used = $userMetrics['total'] + $systemTotal + $publicMetrics['total'] + $databaseSize;

        $quota = $this->resolveQuota();

        return [
            // Archivos subidos por los usuarios (storage/app: disk public/local)
            'users' => [
                'total' => $userMetrics['total'],
                'image' => $userMetrics['image'],
                'pdf'   => $userMetrics['pdf'],
                'other' => $userMetrics['other'],
            ],
            // Archivos del sistema: logs, cache/sesiones del framework y vendor
            'system' => [
                'total'     => $systemTotal,
                'logs'      => $logsSize,
                'framework' => $frameworkSize,
                'vendor'    => $vendorSize,
            ],
            // Contenido estático servido desde public/ (themes, fonts, builds, etc.)
            'public_static' => [
                'total' => $publicMetrics['total'],
            ],
            // Tamaño de la base de datos MySQL (datos + índices)
            'database' => [
                'total' => $databaseSize,
            ],
            // Totales y cuota configurable (parámetro PHD0001)
            'used'            => $used,
            'quota'           => $quota,
            'used_percentage' => $quota > 0 ? min(100, ($used / ($quota * 1024 * 1024 * 1024)) * 100) : 0,
            'scanned_at'      => now()->toIso8601String(),
        ];
    }

    /**
     * Escanea un directorio recursivamente sumando tamaños por tipo de archivo.
     * No sigue symlinks (evita duplicar storage/app/public vía public/storage).
     *
     * @param string $folderPath Ruta a escanear.
     * @param array $excludedPaths Rutas absolutas a excluir del conteo.
     */
    public function scanDirectory(string $folderPath, array $excludedPaths = []): array
    {
        $total = 0;
        $image = 0;
        $pdf = 0;
        $other = 0;

        if (!is_dir($folderPath)) {
            return ['total' => 0, 'image' => 0, 'pdf' => 0, 'other' => 0];
        }

        $normalizedExcluded = array_map('realpath', $excludedPaths);
        $normalizedExcluded = array_filter($normalizedExcluded);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folderPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );

        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $realPath = $file->getRealPath();

            // Saltar archivos dentro de rutas excluidas (por symlink u otros motivos)
            foreach ($normalizedExcluded as $excluded) {
                if ($realPath !== null && str_starts_with($realPath, $excluded . DIRECTORY_SEPARATOR)) {
                    continue 2;
                }
            }

            $fileSize = $file->getSize();
            $total += $fileSize;

            $extension = strtolower($file->getExtension());

            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'svg'])) {
                $image += $fileSize;
            } elseif ($extension === 'pdf') {
                $pdf += $fileSize;
            } else {
                $other += $fileSize;
            }
        }

        return ['total' => $total, 'image' => $image, 'pdf' => $pdf, 'other' => $other];
    }

    /**
     * Tamaño total de un directorio en bytes (0 si no existe).
     */
    public function directorySize(string $folderPath): int
    {
        if (!is_dir($folderPath)) {
            return 0;
        }

        $size = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folderPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Tamaño de la base de datos MySQL (datos + índices) desde information_schema.
     * Devuelve 0 si el driver no es MySQL o la consulta falla.
     */
    public function databaseSizeBytes(): int
    {
        try {
            if (DB::getDriverName() !== 'mysql') {
                return 0;
            }

            $database = DB::connection()->getDatabaseName();

            $result = DB::selectOne(
                'SELECT COALESCE(SUM(data_length + index_length), 0) AS size
                 FROM information_schema.tables
                 WHERE table_schema = ?',
                [$database]
            );

            return (int) ($result->size ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Resuelve la cuota de almacenamiento en GB desde el parámetro PHD0001.
     * Es la única fuente de capacidad: si el parámetro no existe o no es un
     * número válido, devuelve 0 (el indicador mostrará la medición sin
     * porcentaje de cuota).
     */
    public function resolveQuota(): float
    {
        try {
            $paramQuota = Parameter::where('parameter_code', self::QUOTA_PARAMETER)
                ->value('value_default');

            if ($paramQuota !== null && $paramQuota !== '' && is_numeric($paramQuota) && (float) $paramQuota > 0) {
                return (float) $paramQuota;
            }
        } catch (\Throwable $e) {
            // La tabla puede no existir aún durante la migración inicial
        }

        return 0;
    }
}
