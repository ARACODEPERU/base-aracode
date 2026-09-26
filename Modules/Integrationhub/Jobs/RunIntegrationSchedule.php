<?php

namespace Modules\Integrationhub\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Integrationhub\Entities\IntegrationError;
use Modules\Integrationhub\Entities\IntegrationSchedule;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;

/**
 * Ejecuta una programación vencida de Integrationhub, fuera del tick del
 * scheduler.
 *
 * El comando `integrationhub:run-scheduled` solo detecta qué está vencido y
 * despacha este job; el trabajo pesado (HTTP a la API externa, API interna del
 * módulo) vive aquí, con reintentos y con la promesa de que ningún fallo queda
 * en silencio: cada intento deja `last_status`/`last_error` en la programación,
 * el detalle de la llamada en `integration_exec_logs` (lo escribe
 * `IntegrationhubController::execute()`) y, si se agotan los reintentos, un
 * registro en `integration_errors`.
 *
 * La cola es `database` (ver QUEUE_CONNECTION en .env), así que hace falta un
 * worker corriendo (`php artisan queue:work`); sin él los jobs quedan
 * esperando en la tabla `jobs`.
 */
class RunIntegrationSchedule implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Reintentos de la cola antes de pasar por `failed()`. */
    public $tries = 3;

    /** Mayor que el timeout HTTP de la integración (default 30s). */
    public $timeout = 300;

    /**
     * La misma programación no puede tener dos ejecuciones en vuelo: si un
     * intento se atasca, el siguiente tick no lanza otro job para el mismo
     * schedule mientras el lock viva.
     */
    public $uniqueFor = 600;

    public function __construct(public int $scheduleId)
    {
    }

    public function uniqueId(): string
    {
        return 'integrationhub-schedule-' . $this->scheduleId;
    }

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(IntegrationhubController $controller): void
    {
        $schedule = IntegrationSchedule::with(['integration.endpoints'])->find($this->scheduleId);

        if (!$schedule || !$schedule->is_active) {
            return;
        }

        $integration = $schedule->integration;

        if (!$integration || !$integration->is_active) {
            return;
        }

        $schedule->update(['last_status' => 'running', 'last_error' => null]);

        try {
            if ($schedule->target_type === 'module_api') {
                $this->runModuleApi($schedule);
            } else {
                $this->runEndpoints($schedule, $controller);
            }
        } catch (\Throwable $e) {
            $schedule->update([
                'last_status' => 'failed',
                'last_error' => Str::limit($e->getMessage(), 1000),
            ]);

            // Relanza para que la cola reintente (y, al agotarse, llame a failed()).
            throw $e;
        }

        $schedule->update([
            'last_status' => 'success',
            'last_error' => null,
            'last_executed_at' => now(),
        ]);

        $integration->update(['last_executed_at' => now()]);
    }

    /**
     * Se llama cuando se agotan los reintentos: deja constancia en la bitácora
     * de errores y cierra el estado de la programación.
     */
    public function failed(\Throwable $exception): void
    {
        $message = Str::limit($exception->getMessage(), 1000);

        $schedule = IntegrationSchedule::find($this->scheduleId);

        if ($schedule) {
            $schedule->update(['last_status' => 'failed', 'last_error' => $message]);
        }

        IntegrationError::create([
            'message' => 'Programación #' . $this->scheduleId . ' falló tras los reintentos: ' . $message,
            'source' => 'integrationhub_schedule',
        ]);
    }

    /**
     * Ejecuta el/los endpoints de la integración, igual que lo hacía el comando
     * de forma síncrona, pero fallando en serio cuando el endpoint responde con
     * error (antes el fallo solo quedaba en el log de ejecución y la
     * programación parecía exitosa).
     */
    private function runEndpoints(IntegrationSchedule $schedule, IntegrationhubController $controller): void
    {
        $integration = $schedule->integration;

        $endpoints = $schedule->endpoint_id
            ? $integration->endpoints->where('id', $schedule->endpoint_id)
            : $integration->endpoints->where('is_active', true);

        $executed = 0;

        foreach ($endpoints as $endpoint) {
            if (!$endpoint->is_active) {
                continue;
            }

            $request = Request::create('', 'POST', [
                'endpoint_id' => $endpoint->id,
                'variables' => $schedule->payload ?? [],
            ]);

            $response = $controller->execute($request, $integration->id);
            $executed++;

            if ($response->getStatusCode() >= 400) {
                $detail = (array) $response->getData(true);
                $message = $detail['message'] ?? 'sin detalle';

                throw new \RuntimeException(sprintf(
                    'El endpoint "%s" respondió HTTP %d: %s',
                    $endpoint->name,
                    $response->getStatusCode(),
                    is_scalar($message) ? $message : json_encode($message)
                ));
            }
        }

        if ($executed === 0) {
            throw new \RuntimeException(
                'La programación #' . $schedule->id . ' no tiene endpoints activos para ejecutar.'
            );
        }
    }

    /**
     * Ejecuta una ruta API interna del módulo. A diferencia de lo que hacía el
     * comando (que ignoraba la respuesta), aquí una respuesta 4xx/5xx es un
     * fallo real: el middleware `localhost.only`, un 404 o un error del
     * controlador ya no pasan en silencio.
     */
    private function runModuleApi(IntegrationSchedule $schedule): void
    {
        $route = collect(Route::getRoutes())->first(
            fn ($route) => $route->getName() === $schedule->api_route_name
        );

        if (!$route) {
            throw new \RuntimeException(
                'Ruta API no encontrada para la programación #' . $schedule->id . ': ' . $schedule->api_route_name
            );
        }

        $method = collect($route->methods())
            ->reject(fn ($method) => $method === 'HEAD')
            ->first() ?? 'POST';

        $request = Request::create('/' . ltrim($route->uri(), '/'), $method, $schedule->payload ?? [], [], [], [
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        $response = app()->handle($request);

        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException(sprintf(
                'La API "%s" respondió HTTP %d',
                $schedule->api_route_name,
                $response->getStatusCode()
            ));
        }
    }
}
