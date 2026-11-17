<?php

namespace Modules\Performance\Console;

use Illuminate\Console\Command;
use Modules\Performance\Entities\PerformanceAlert;
use Modules\Performance\Services\ImageBackupService;
use Modules\Performance\Services\ImageInventoryService;

/**
 * Auditoría diaria del peso de las imágenes.
 *
 * Marca como alerta cada imagen nueva (modificada en los últimos días) que
 * supere el umbral configurado, para que el administrador la revise desde
 * Rendimiento → Imágenes. Además purga las copias de seguridad y las alertas
 * ya revisadas que superan su retención.
 */
class AuditImagesCommand extends Command
{
    protected $signature = 'performance:audit';

    protected $description = 'Detecta imágenes nuevas y pesadas, y limpia copias de seguridad antiguas.';

    public function handle(ImageInventoryService $inventory, ImageBackupService $backups): int
    {
        $threshold = ((int) config('performance.oversized_kb', 300)) * 1024;
        $images = $inventory->all(true);

        $created = 0;

        foreach ($images as $image) {
            if ($image['bytes'] < $threshold || ! $image['is_new']) {
                continue;
            }

            $exists = PerformanceAlert::query()
                ->where('path', $image['path'])
                ->whereNull('reviewed_at')
                ->exists();

            if ($exists) {
                continue;
            }

            PerformanceAlert::create([
                'path'        => $image['path'],
                'bytes'       => $image['bytes'],
                'reason'      => 'Nueva imagen pesada',
                'detected_at' => now(),
            ]);

            $created++;
        }

        $removed = $backups->cleanup((int) config('performance.backup_days', 30));

        $pruned = PerformanceAlert::query()
            ->whereNotNull('reviewed_at')
            ->where('reviewed_at', '<', now()->subDays(90))
            ->delete();

        $this->info("Imágenes revisadas: " . count($images));
        $this->info("Alertas nuevas: {$created}");
        $this->info("Copias de seguridad eliminadas: {$removed}");
        $this->info("Alertas antiguas eliminadas: {$pruned}");

        return self::SUCCESS;
    }
}
