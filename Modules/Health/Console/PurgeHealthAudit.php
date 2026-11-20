<?php

namespace Modules\Health\Console;

use Illuminate\Console\Command;
use Modules\Health\Entities\HealActivity;
use Modules\Health\Support\HealthAudit;

/**
 * Aplica la retención de la auditoría.
 *
 * Por defecto solo purga accesos (`access`) más antiguos que 5 años (1825
 * días). La auditoría clínica y de seguridad nunca se borra con este comando:
 * la historia clínica debe conservarse completa.
 */
class PurgeHealthAudit extends Command
{
    protected $signature = 'health:audit:purge
        {--days=1825 : Días de retención (5 años por defecto)}
        {--category=access : Categoría a purgar (access o admin)}
        {--dry-run : Solo muestra cuántas entradas se borrarían}';

    protected $description = 'Purga los accesos de auditoría fuera del plazo de retención';

    /**
     * Categorías que se pueden purgar. `clinical` y `security` quedan fuera.
     */
    private const PERMITIDAS = ['access', 'admin'];

    public function handle(): int
    {
        $categoria = (string) $this->option('category');
        $dias = max(1, (int) $this->option('days'));

        if (!in_array($categoria, self::PERMITIDAS, true)) {
            $this->error("Solo se pueden purgar las categorías: " . implode(', ', self::PERMITIDAS) . '.');

            return self::FAILURE;
        }

        $corte = now()->subDays($dias);

        $query = HealActivity::query()
            ->where('category', $categoria)
            ->where('created_at', '<', $corte);

        $total = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info("Se purgarían {$total} entradas de la categoría {$categoria} anteriores a {$corte->toDateString()}.");

            return self::SUCCESS;
        }

        $borradas = 0;

        // Borrado masivo: no dispara los eventos del modelo (la purga es el
        // único proceso autorizado a retirar filas de auditoría).
        $query->select('id')->chunkById(500, function ($filas) use (&$borradas) {
            $ids = $filas->pluck('id')->all();
            $borradas += HealActivity::query()->whereIn('id', $ids)->delete();
        });

        HealthAudit::record('retention_purge', [
            'subject_type' => HealActivity::class,
            'category' => HealthAudit::CATEGORY_SECURITY,
            'metadata' => [
                'categoria_purgada' => $categoria,
                'dias_retencion' => $dias,
                'corte' => $corte->toDateTimeString(),
                'entradas_eliminadas' => $borradas,
                'subject_name' => 'Registro de Actividades',
            ],
        ]);

        $this->info("Purga completada: {$borradas} entradas eliminadas (categoría {$categoria}).");

        return self::SUCCESS;
    }
}
