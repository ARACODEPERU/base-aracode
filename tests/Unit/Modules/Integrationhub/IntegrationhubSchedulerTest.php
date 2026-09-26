<?php

namespace Tests\Unit\Modules\Integrationhub;

use App\Console\Commands\RunScheduledIntegrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Modules\Integrationhub\Jobs\RunIntegrationSchedule;
use Tests\TestCase;
use Tests\Unit\Modules\Integrationhub\Concerns\BuildsIntegrationhubSchema;

/**
 * El comando del scheduler: detecta qué está vencido y lo encola.
 *
 * La promesa que se fija aquí: el comando no ejecuta integraciones (eso es
 * trabajo del job), nunca despacha dos veces la misma programación aunque se
 * corra a cada rato, deja una señal de vida para saber si el scheduler está
 * vivo, y no encola nada de una integración apagada (pero sí realinea su
 * calendario).
 */
class IntegrationhubSchedulerTest extends TestCase
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

    public function test_despacha_solo_las_programaciones_vencidas(): void
    {
        Queue::fake();

        $integration = $this->makeIntegration();
        $endpoint = $this->makeEndpoint($integration);

        $vencida = $this->makeSchedule($integration, [
            'endpoint_id' => $endpoint->id,
            'next_execution_at' => now()->subMinutes(5),
        ]);

        $futura = $this->makeSchedule($integration, [
            'endpoint_id' => $endpoint->id,
            'cron_expression' => '0 0 1 1 *',
            'next_execution_at' => now()->addHour(),
        ]);

        $inactiva = $this->makeSchedule($integration, [
            'endpoint_id' => $endpoint->id,
            'is_active' => false,
        ]);

        $this->artisan('integrationhub:run-scheduled')->assertSuccessful();

        Queue::assertPushed(RunIntegrationSchedule::class, 1);
        Queue::assertPushed(
            RunIntegrationSchedule::class,
            fn (RunIntegrationSchedule $job) => $job->scheduleId === $vencida->id
        );

        // El turno se consume al despachar: el calendario ya mira al futuro.
        $vencida->refresh();
        $this->assertTrue($vencida->next_execution_at->gt(now()));
        $this->assertSame('pending', $vencida->last_status);

        $this->assertSame('0 0 1 1 *', $futura->fresh()->cron_expression);
        $this->assertNull($inactiva->fresh()->last_status);
    }

    public function test_dos_corridas_en_el_mismo_minuto_no_despachan_dos_veces(): void
    {
        Queue::fake();

        $integration = $this->makeIntegration();
        $endpoint = $this->makeEndpoint($integration);
        $schedule = $this->makeSchedule($integration, ['endpoint_id' => $endpoint->id]);

        $this->artisan('integrationhub:run-scheduled')->assertSuccessful();
        $this->artisan('integrationhub:run-scheduled')->assertSuccessful();

        Queue::assertPushed(RunIntegrationSchedule::class, 1);
    }

    public function test_una_programacion_nunca_ejecutada_se_alinea_sin_despachar(): void
    {
        Queue::fake();

        $integration = $this->makeIntegration();
        $endpoint = $this->makeEndpoint($integration);
        $schedule = $this->makeSchedule($integration, [
            'endpoint_id' => $endpoint->id,
            'cron_expression' => '0 0 1 1 *',
            'next_execution_at' => null,
        ]);

        $this->artisan('integrationhub:run-scheduled')->assertSuccessful();

        Queue::assertPushed(RunIntegrationSchedule::class, 0);

        $schedule->refresh();
        $this->assertNotNull($schedule->next_execution_at);
        $this->assertEquals(
            now()->copy()->addYear()->startOfYear(),
            $schedule->next_execution_at
        );
    }

    public function test_una_ejecucion_perdida_se_despacha_una_vez_y_se_realinea(): void
    {
        Queue::fake();

        $integration = $this->makeIntegration();
        $endpoint = $this->makeEndpoint($integration);

        // Diaria a medianoche, con el scheduler caído tres días.
        $schedule = $this->makeSchedule($integration, [
            'endpoint_id' => $endpoint->id,
            'cron_expression' => '0 0 * * *',
            'next_execution_at' => now()->subDays(3),
        ]);

        $this->artisan('integrationhub:run-scheduled')->assertSuccessful();

        Queue::assertPushed(RunIntegrationSchedule::class, 1);

        $schedule->refresh();
        $this->assertTrue($schedule->next_execution_at->gt(now()));
    }

    public function test_una_integracion_apagada_no_se_encola(): void
    {
        Queue::fake();

        $integration = $this->makeIntegration(['is_active' => false]);
        $endpoint = $this->makeEndpoint($integration);
        $schedule = $this->makeSchedule($integration, ['endpoint_id' => $endpoint->id]);

        $this->artisan('integrationhub:run-scheduled')->assertSuccessful();

        Queue::assertPushed(RunIntegrationSchedule::class, 0);

        // El calendario sí avanza: no se reintentará en cada tick.
        $this->assertTrue($schedule->fresh()->next_execution_at->gt(now()));
    }

    public function test_deja_una_senal_de_vida_para_saber_si_el_scheduler_esta_vivo(): void
    {
        Queue::fake();

        $this->artisan('integrationhub:run-scheduled')->assertSuccessful();

        $this->assertNotNull(Cache::get(RunScheduledIntegrations::HEARTBEAT_CACHE_KEY));
    }
}
