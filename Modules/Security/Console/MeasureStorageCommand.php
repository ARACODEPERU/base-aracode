<?php

namespace Modules\Security\Console;

use Illuminate\Console\Command;
use Modules\Security\Services\StorageMetricsService;

class MeasureStorageCommand extends Command
{
    /**
     * Mide el almacenamiento del proyecto y guarda el resultado en caché
     * (24 h). Programado diariamente; también puede ejecutarse manualmente.
     */
    protected $signature = 'security:measure-storage';

    protected $description = 'Mide el uso de almacenamiento del proyecto (usuarios, sistema, public, base de datos) y guarda el resultado en caché';

    public function handle(StorageMetricsService $metrics): int
    {
        $this->info('Midiendo almacenamiento del proyecto...');

        $data = $metrics->measure();

        $gb = fn ($bytes) => number_format($bytes / (1024 ** 3), 2) . ' GB';

        $this->table(
            ['Concepto', 'Tamaño'],
            [
                ['Archivos de usuarios (storage/app)', $gb($data['users']['total'])],
                ['  - Imágenes', $gb($data['users']['image'])],
                ['  - PDF', $gb($data['users']['pdf'])],
                ['  - Otros', $gb($data['users']['other'])],
                ['Archivos del sistema (logs+framework+vendor)', $gb($data['system']['total'])],
                ['  - Logs', $gb($data['system']['logs'])],
                ['  - Framework (caché/sesiones)', $gb($data['system']['framework'])],
                ['  - Vendor (paquetes)', $gb($data['system']['vendor'])],
                ['Público estático (public/)', $gb($data['public_static']['total'])],
                ['Base de datos', $gb($data['database']['total'])],
                ['TOTAL USADO', $gb($data['used'])],
                ['Cuota configurada (parámetro ' . StorageMetricsService::QUOTA_PARAMETER . ')', $data['quota'] . ' GB'],
            ]
        );

        $this->info('Medición guardada en caché hasta: ' . now()->addMinutes(StorageMetricsService::CACHE_TTL_MINUTES)->format('d/m/Y H:i'));

        return self::SUCCESS;
    }
}
