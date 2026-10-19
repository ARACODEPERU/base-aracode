<?php

namespace Modules\Security\Console;

use Illuminate\Console\Command;
use Modules\Security\Entities\SecurityAlertSetting;
use Modules\Security\Services\ErrorLogService;
use Throwable;

/**
 * Borra los errores guardados que superan la retención configurada.
 *
 * Corre programado todos los días (Seguridad → Alertas define los días) y
 * también puede ejecutarse a mano:
 *
 *   php artisan security:prune-error-logs            # usa la retención guardada
 *   php artisan security:prune-error-logs --days=7   # fuerza 7 días
 *   php artisan security:prune-error-logs --all      # borra todo el contexto
 *   php artisan security:prune-error-logs --logs     # y también los .log rotados
 *
 * Con `--logs` además elimina los archivos storage/logs/*.log que ya no son el
 * log actual (laravel.log) y superan la misma antigüedad: son los que hacen
 * crecer el disco. Sin ese flag no se toca ningún archivo.
 */
class PruneErrorLogsCommand extends Command
{
    protected $signature = 'security:prune-error-logs
                            {--days= : Días de retención (por defecto, el valor configurado en Seguridad → Alertas)}
                            {--all : Borra TODOS los errores guardados, sin importar la antigüedad}
                            {--logs : Borra también los archivos .log rotados más viejos que la retención}';

    protected $description = 'Borra el contexto de errores vencido (módulo Security) para no acumular datos en el disco';

    public function handle(ErrorLogService $logs): int
    {
        $settings = SecurityAlertSetting::current();

        if ($this->option('all')) {
            $deleted = $logs->clear();
            $this->warn("Se eliminaron TODOS los errores guardados: {$deleted} fila(s).");

            return self::SUCCESS;
        }

        if (! $settings->autoPurge() && ! $this->option('days')) {
            $this->info('El borrado automático de errores está desactivado (o la retención es 0). Nada que hacer.');

            return self::SUCCESS;
        }

        $days = $this->option('days') !== null
            ? max(1, min(3650, (int) $this->option('days')))
            : $settings->retentionDays();

        if ($days <= 0) {
            $this->info('La retención configurada es 0 días: no se borra nada.');

            return self::SUCCESS;
        }

        $deleted = $logs->prune($days);
        $this->info("Errores eliminados (más de {$days} día(s)): {$deleted} fila(s) con todo su contexto.");

        if ($this->option('logs')) {
            $files = $this->pruneLogFiles($days);
            $this->info("Archivos de log rotados eliminados: {$files}.");
        }

        return self::SUCCESS;
    }

    /**
     * Borra los archivos de log rotados más viejos que la retención.
     *
     * Nunca toca el log en uso (laravel.log) ni archivos que no sean .log.
     */
    private function pruneLogFiles(int $days): int
    {
        $deleted = 0;

        try {
            $directory = storage_path('logs');
            $limit = now()->subDays($days)->getTimestamp();
            $current = 'laravel.log';

            foreach (glob($directory . DIRECTORY_SEPARATOR . '*.log') ?: [] as $file) {
                if (! is_file($file) || basename($file) === $current) {
                    continue;
                }

                if (@filemtime($file) < $limit && @unlink($file)) {
                    $deleted++;
                }
            }
        } catch (Throwable) {
            // Un archivo que no se puede borrar no debe romper el comando.
        }

        return $deleted;
    }
}
