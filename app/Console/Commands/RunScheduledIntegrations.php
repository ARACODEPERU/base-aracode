<?php

namespace App\Console\Commands;

use App\Services\IntegrationhubCronExpression;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Integrationhub\Entities\IntegrationSchedule;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;

class RunScheduledIntegrations extends Command
{
    protected $signature = 'integrationhub:run-scheduled';

    protected $description = 'Ejecuta las integraciones programadas vencidas de Integrationhub.';

    public function handle(IntegrationhubCronExpression $cron): int
    {
        $now = now()->startOfMinute();

        $schedules = IntegrationSchedule::with(['integration.endpoints'])
            ->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('next_execution_at')
                    ->orWhere('next_execution_at', '<=', $now);
            })
            ->get();

        foreach ($schedules as $schedule) {
            if (is_null($schedule->next_execution_at) && !$cron->isDue($schedule->cron_expression, $now)) {
                $schedule->update([
                    'next_execution_at' => $cron->nextRunDate($schedule->cron_expression, $now),
                ]);
                continue;
            }

            if (!$cron->isDue($schedule->cron_expression, $now) && $schedule->next_execution_at?->gt($now)) {
                continue;
            }

            $this->runSchedule($schedule, $cron, $now);
        }

        return self::SUCCESS;
    }

    private function runSchedule(IntegrationSchedule $schedule, IntegrationhubCronExpression $cron, $now): void
    {
        $integration = $schedule->integration;

        if (!$integration || !$integration->is_active) {
            $schedule->update([
                'next_execution_at' => $cron->nextRunDate($schedule->cron_expression, $now),
            ]);
            return;
        }

        if ($schedule->target_type === 'module_api') {
            $this->runModuleApiSchedule($schedule);

            $schedule->update([
                'last_executed_at' => now(),
                'next_execution_at' => $cron->nextRunDate($schedule->cron_expression, $now),
            ]);

            $integration->update([
                'last_executed_at' => now(),
            ]);

            return;
        }

        $endpoints = $schedule->endpoint_id
            ? $integration->endpoints->where('id', $schedule->endpoint_id)
            : $integration->endpoints->where('is_active', true);

        foreach ($endpoints as $endpoint) {
            if (!$endpoint->is_active) {
                continue;
            }

            $request = Request::create('', 'POST', [
                'endpoint_id' => $endpoint->id,
                'variables' => $schedule->payload ?? [],
            ]);

            app(IntegrationhubController::class)->execute($request, $integration->id);
        }

        $schedule->update([
            'last_executed_at' => now(),
            'next_execution_at' => $cron->nextRunDate($schedule->cron_expression, $now),
        ]);

        $integration->update([
            'last_executed_at' => now(),
        ]);
    }

    private function runModuleApiSchedule(IntegrationSchedule $schedule): void
    {
        $route = collect(Route::getRoutes())->first(function ($route) use ($schedule) {
            return $route->getName() === $schedule->api_route_name;
        });

        if (!$route) {
            $this->warn("Ruta API no encontrada para la programacion {$schedule->id}: {$schedule->api_route_name}");
            return;
        }

        $method = collect($route->methods())
            ->reject(fn ($method) => $method === 'HEAD')
            ->first() ?? 'POST';

        $request = Request::create('/' . ltrim($route->uri(), '/'), $method, $schedule->payload ?? [], [], [], [
            'HTTP_HOST' => 'localhost',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        app()->handle($request);
    }
}
