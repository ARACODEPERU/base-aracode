<?php

namespace Modules\Health\Console;

use Illuminate\Console\Command;
use Modules\Health\Entities\HealActivity;
use Modules\Health\Support\HealthAudit;

/**
 * Verifica que el registro de auditoría no haya sido alterado.
 *
 * Recalcula la huella de cada entrada y comprueba el eslabón con la anterior.
 * Las filas antiguas (sin entry_hash) se informan como previas a la cadena.
 */
class VerifyHealthAudit extends Command
{
    protected $signature = 'health:audit:verify
        {--from= : Fecha inicial (Y-m-d)}
        {--to= : Fecha final (Y-m-d)}
        {--limit=0 : Máximo de entradas a revisar (0 = todas)}';

    protected $description = 'Verifica la integridad de la cadena de auditoría de Salud';

    public function handle(): int
    {
        $query = HealActivity::query()->orderBy('id');

        if ($this->option('from')) {
            $query->whereDate('created_at', '>=', $this->option('from'));
        }

        if ($this->option('to')) {
            $query->whereDate('created_at', '<=', $this->option('to'));
        }

        $limite = (int) $this->option('limit');
        if ($limite > 0) {
            $query->limit($limite);
        }

        $total = 0;
        $legacy = 0;
        $huecos = 0;
        $alteradas = 0;
        $anterior = null;

        $query->chunk(500, function ($actividades) use (&$total, &$legacy, &$huecos, &$alteradas, &$anterior) {
            foreach ($actividades as $actividad) {
                $total++;

                if (!$actividad->entry_hash) {
                    $legacy++;
                    $anterior = $actividad;
                    continue;
                }

                $esperado = hash('sha256', implode('|', [
                    (string) $actividad->previous_hash,
                    $actividad->activity_type,
                    (string) $actividad->subject_type,
                    (string) $actividad->subject_id,
                    (string) $actividad->patient_id,
                    HealthAudit::canonicalJson($actividad->metadata ?? []),
                    optional($actividad->created_at)->toDateTimeString(),
                ]));

                if (!hash_equals($esperado, (string) $actividad->entry_hash)) {
                    $alteradas++;
                    $this->error("Entrada alterada: #{$actividad->id} ({$actividad->created_at})");
                }

                if ($anterior && $anterior->entry_hash) {
                    if ($actividad->previous_hash !== $anterior->entry_hash) {
                        // Un salto puede ser una purga por retención; se informa.
                        if ((int) $actividad->id !== (int) $anterior->id + 1) {
                            $huecos++;
                        } else {
                            $alteradas++;
                            $this->error("Cadena rota entre #{$anterior->id} y #{$actividad->id}.");
                        }
                    }
                }

                $anterior = $actividad;
            }
        });

        $this->newLine();
        $this->table(
            ['Revisadas', 'Previas a la cadena', 'Huecos por purga', 'Alteradas'],
            [[$total, $legacy, $huecos, $alteradas]]
        );

        if ($alteradas > 0) {
            $this->error('La auditoría presenta alteraciones.');

            return self::FAILURE;
        }

        $this->info('Cadena de auditoría íntegra.');

        return self::SUCCESS;
    }
}
