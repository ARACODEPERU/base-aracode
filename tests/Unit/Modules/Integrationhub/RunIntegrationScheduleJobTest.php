<?php

namespace Tests\Unit\Modules\Integrationhub;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Integrationhub\Entities\IntegrationError;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;
use Modules\Integrationhub\Jobs\RunIntegrationSchedule;
use Tests\TestCase;
use Tests\Unit\Modules\Integrationhub\Concerns\BuildsIntegrationhubSchema;

/**
 * El job que ejecuta una programación vencida.
 *
 * El contrato: la ejecución nunca es silenciosa. Un endpoint que responde con
 * error (o una API interna que devuelve 4xx/5xx, o que ya no existe) relanza la
 * excepción para que la cola reintente, deja el motivo en la programación y, al
 * agotarse los reintentos, `failed()` lo registra en la bitácora de errores.
 *
 * El controlador se sustituye por un doble para no hacer HTTP real: lo que se
 * prueba aquí es el trabajo del job (selección de endpoints, variables del
 * payload, estado y registro), no el armado de la llamada del controlador.
 */
class RunIntegrationScheduleJobTest extends TestCase
{
    use BuildsIntegrationhubSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropIntegrationhubSchema();
        $this->createIntegrationhubSchema();
    }

    protected function tearDown(): void
    {
        $this->dropIntegrationhubSchema();

        parent::tearDown();
    }

    public function test_ejecuta_el_endpoint_con_las_variables_del_borrador_y_marca_exito(): void
    {
        $integration = $this->makeIntegration();
        $endpoint = $this->makeEndpoint($integration);
        $schedule = $this->makeSchedule($integration, [
            'endpoint_id' => $endpoint->id,
            'payload' => ['contacto' => 'Ana', 'telefono' => '999'],
        ]);

        $controller = $this->controllerDouble(200);

        (new RunIntegrationSchedule($schedule->id))->handle($controller);

        $this->assertCount(1, $controller->calls);
        $this->assertSame($integration->id, $controller->calls[0]['integration_id']);
        $this->assertSame($endpoint->id, $controller->calls[0]['endpoint_id']);
        $this->assertSame(['contacto' => 'Ana', 'telefono' => '999'], $controller->calls[0]['variables']);

        $schedule->refresh();
        $this->assertSame('success', $schedule->last_status);
        $this->assertNull($schedule->last_error);
        $this->assertNotNull($schedule->last_executed_at);
        $this->assertNotNull($integration->fresh()->last_executed_at);
    }

    public function test_un_fallo_del_endpoint_se_relanza_y_deja_el_motivo(): void
    {
        $integration = $this->makeIntegration();
        $endpoint = $this->makeEndpoint($integration);
        $schedule = $this->makeSchedule($integration, ['endpoint_id' => $endpoint->id]);

        $job = new RunIntegrationSchedule($schedule->id);

        try {
            $job->handle($this->controllerDouble(500, 'todo mal'));
            $this->fail('El job debía relanzar el fallo para que la cola reintente.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('HTTP 500', $e->getMessage());
            $this->assertStringContainsString('todo mal', $e->getMessage());
        }

        $schedule->refresh();
        $this->assertSame('failed', $schedule->last_status);
        $this->assertStringContainsString('HTTP 500', $schedule->last_error);
    }

    public function test_sin_endpoints_activos_la_programacion_falla(): void
    {
        $integration = $this->makeIntegration();
        $this->makeEndpoint($integration, ['is_active' => false]);
        $schedule = $this->makeSchedule($integration, ['endpoint_id' => null]);

        $job = new RunIntegrationSchedule($schedule->id);

        try {
            $job->handle($this->controllerDouble(200));
            $this->fail('Ejecutar cero endpoints debía ser un fallo, no un éxito silencioso.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('no tiene endpoints activos', $e->getMessage());
        }

        $this->assertSame('failed', $schedule->fresh()->last_status);
    }

    public function test_al_agotarse_los_reintentos_se_registra_en_la_bitacora_de_errores(): void
    {
        $integration = $this->makeIntegration();
        $endpoint = $this->makeEndpoint($integration);
        $schedule = $this->makeSchedule($integration, ['endpoint_id' => $endpoint->id]);

        (new RunIntegrationSchedule($schedule->id))->failed(new \RuntimeException('boom'));

        $error = IntegrationError::first();
        $this->assertNotNull($error);
        $this->assertSame('integrationhub_schedule', $error->source);
        $this->assertStringContainsString('boom', $error->message);
        $this->assertStringContainsString('#' . $schedule->id, $error->message);

        $schedule->refresh();
        $this->assertSame('failed', $schedule->last_status);
        $this->assertStringContainsString('boom', $schedule->last_error);
    }

    public function test_una_programacion_inactiva_no_se_ejecuta(): void
    {
        $integration = $this->makeIntegration();
        $endpoint = $this->makeEndpoint($integration);
        $schedule = $this->makeSchedule($integration, [
            'endpoint_id' => $endpoint->id,
            'is_active' => false,
        ]);

        $controller = $this->controllerDouble(200);

        (new RunIntegrationSchedule($schedule->id))->handle($controller);

        $this->assertCount(0, $controller->calls);
        $this->assertNull($schedule->fresh()->last_status);
    }

    public function test_module_api_ejecuta_la_ruta_interna_del_modulo(): void
    {
        Route::post('api/integrationhub/__test_target', fn () => response()->json(['ok' => true]))
            ->name('integrationhub.test_target');

        $integration = $this->makeIntegration();
        $schedule = $this->makeSchedule($integration, [
            'target_type' => 'module_api',
            'api_route_name' => 'integrationhub.test_target',
            'endpoint_id' => null,
            'payload' => ['variables' => []],
        ]);

        (new RunIntegrationSchedule($schedule->id))->handle($this->controllerDouble(200));

        $this->assertSame('success', $schedule->fresh()->last_status);
    }

    public function test_module_api_con_respuesta_de_error_es_un_fallo(): void
    {
        Route::post('api/integrationhub/__test_fail', fn () => response()->json(['ok' => false], 500))
            ->name('integrationhub.test_fail');

        $integration = $this->makeIntegration();
        $schedule = $this->makeSchedule($integration, [
            'target_type' => 'module_api',
            'api_route_name' => 'integrationhub.test_fail',
            'endpoint_id' => null,
        ]);

        $job = new RunIntegrationSchedule($schedule->id);

        try {
            $job->handle($this->controllerDouble(200));
            $this->fail('Una API interna que responde 500 debía ser un fallo.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('HTTP 500', $e->getMessage());
        }

        $this->assertSame('failed', $schedule->fresh()->last_status);
    }

    public function test_module_api_con_ruta_inexistente_es_un_fallo(): void
    {
        $integration = $this->makeIntegration();
        $schedule = $this->makeSchedule($integration, [
            'target_type' => 'module_api',
            'api_route_name' => 'integrationhub.ya_no_existe',
            'endpoint_id' => null,
        ]);

        $job = new RunIntegrationSchedule($schedule->id);

        try {
            $job->handle($this->controllerDouble(200));
            $this->fail('Una ruta API borrada debía ser un fallo visible.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Ruta API no encontrada', $e->getMessage());
        }

        $this->assertSame('failed', $schedule->fresh()->last_status);
    }

    /**
     * Doble del controlador de ejecución: registra las llamadas y responde con
     * el estado que indique la prueba, sin HTTP real.
     */
    private function controllerDouble(int $status, string $message = 'ok'): IntegrationhubController
    {
        return new class($status, $message) extends IntegrationhubController {
            public array $calls = [];

            public function __construct(private int $status, private string $message)
            {
            }

            public function execute(Request $request, int $id)
            {
                $this->calls[] = [
                    'integration_id' => $id,
                    'endpoint_id' => $request->input('endpoint_id'),
                    'variables' => $request->input('variables'),
                ];

                return response()->json(['message' => $this->message], $this->status);
            }
        };
    }
}
