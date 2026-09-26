<?php

namespace App\Console\Commands;

use App\Services\IntegrationhubCronExpression;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Integrationhub\Entities\IntegrationError;
use Modules\Integrationhub\Entities\IntegrationSchedule;
use Modules\Integrationhub\Jobs\RunIntegrationSchedule;

/**
 * Despacha las integraciones programadas vencidas de Integrationhub.
 *
 * El comando ya no ejecuta integraciones: solo decide qué está vencido y
 * encola un `RunIntegrationSchedule` por programación (cola `database`). Así el
 * tick de cada minuto termina rápido aunque la API externa tarde, y la ejecución
 * tiene reintentos y registro de fallos.
 *
 * La reserva del turno es atómica: se avanza `next_execution_at` antes de
 * despachar y solo si la fila seguía vencida, de modo que dos corridas del
 * comando (o un schedule:work y un cron a la vez) no ejecuten la misma
 * programación dos veces. Lo que sí se permite es una única ejecución perdida:
 * si el scheduler estuvo caído, la programación atrasada se despacha una vez y
 * se realinea con su próxima ejecución.
 *
 * En cada corrida se guarda una señal de vida (heartbeat) que la pantalla de
 * Programaciones usa para avisar si el scheduler está detenido.
 */
class RunScheduledIntegrations extends Command
{
    /**
     * Clave de caché con el último tick del scheduler (ISO-8601).
     */
    public const HEARTBEAT_CACHE_KEY = 'integrationhub_scheduler_last_tick';

    protected $signature = 'integrationhub:run-scheduled';

    protected $description = 'Despacha a la cola las integraciones programadas vencidas de Integrationhub.';

    public function handle(IntegrationhubCronExpression $cron): int
    {
        $now = now()->startOfMinute();

        Cache::put(self::HEARTBEAT_CACHE_KEY, now()->toIso8601String(), now()->addDay());

        $due = IntegrationSchedule::with(['integration'])
            ->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('next_execution_at')
                    ->orWhere('next_execution_at', '<=', $now);
            })
            ->get();

        foreach ($due as $schedule) {
            $this->dispatchDue($schedule, $cron, $now);
        }

        return self::SUCCESS;
    }

    private function dispatchDue(IntegrationSchedule $schedule, IntegrationhubCronExpression $cron, $now): void
    {
        $nextExecution = $cron->nextRunDate($schedule->cron_expression, $now);

        if (is_null($schedule->next_execution_at) && !$cron->isDue($schedule->cron_expression, $now)) {
            // Nunca corrió y todavía no toca: solo se alinea el calendario.
            $schedule->update(['next_execution_at' => $nextExecution]);

            return;
        }

        // Reserva el turno: si otra corrida del comando ya avanzó la fila, esta
        // no vuelve a despachar.
        $claimed = IntegrationSchedule::whereKey($schedule->id)
            ->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('next_execution_at')
                    ->orWhere('next_execution_at', '<=', $now);
            })
            ->update([
                'next_execution_at' => $nextExecution,
                'last_status' => 'pending',
            ]);

        if (!$claimed) {
            return;
        }

        $integration = $schedule->integration;

        if (!$integration || !$integration->is_active) {
            // El turno ya se consumió y el calendario quedó realineado: una
            // integración apagada no se encola.
            return;
        }

        try {
            RunIntegrationSchedule::dispatch($schedule->id);
        } catch (\Throwable $e) {
            $message = 'No se pudo despachar la programación #' . $schedule->id . ': ' . $e->getMessage();

            $schedule->update([
                'last_status' => 'failed',
                'last_error' => Str::limit($message, 1000),
            ]);

            IntegrationError::create([
                'message' => $message,
                'source' => 'integrationhub_schedule',
            ]);
        }
    }
}
