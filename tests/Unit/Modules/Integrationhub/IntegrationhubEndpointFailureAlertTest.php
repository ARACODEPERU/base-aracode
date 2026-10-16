<?php

namespace Tests\Unit\Modules\Integrationhub;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Modules\Integrationhub\Entities\Integration;
use Modules\Integrationhub\Entities\IntegrationExecLog;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;
use Modules\Security\Entities\SecurityAlertRecipient;
use Modules\Security\Entities\SecurityErrorLog;
use Modules\Security\Jobs\SendSecurityErrorAlert;
use Tests\TestCase;
use Tests\Unit\Modules\Integrationhub\Concerns\BuildsIntegrationhubSchema;
use Tests\Unit\Modules\Security\Concerns\BuildsSecurityAlertSchema;

/**
 * Un fallo al usar Integrationhub como endpoint no puede detener al sistema.
 *
 * El contrato: quien llama (job, webhook, pantalla, API del módulo) recibe la
 * respuesta de error en vez de una excepción, el intento queda en el Historial de
 * Integrationhub y, además, el fallo se avisa por el canal de alertas de
 * Seguridad → Alertas (ErrorAlertService escucha los logs de la aplicación).
 */
class IntegrationhubEndpointFailureAlertTest extends TestCase
{
    use BuildsIntegrationhubSchema;
    use BuildsSecurityAlertSchema;

    /**
     * Migraciones extra que necesita el armado de la llamada del controlador
     * (autenticación, mapeos de campos y el Historial).
     */
    private const EXTRA_MIGRATIONS = [
        'Modules/Integrationhub/Database/Migrations/2026_01_01_000002_create_integration_auth_table.php',
        'Modules/Integrationhub/Database/Migrations/2026_01_01_000005_create_integration_field_maps_table.php',
        'Modules/Integrationhub/Database/Migrations/2026_01_01_000007_create_integration_exec_logs_table.php',
        'Modules/Integrationhub/Database/Migrations/2026_04_29_000001_add_field_location_to_integration_field_maps_table.php',
        'Modules/Integrationhub/Database/Migrations/2026_05_16_000005_add_batch_id_to_integration_exec_logs_table.php',
    ];

    private const EXTRA_TABLES = ['integration_exec_logs', 'integration_field_maps', 'integration_auth'];

    protected function setUp(): void
    {
        parent::setUp();

        // La ventana de anti-duplicados de las alertas vive en caché.
        Cache::flush();

        $this->dropIntegrationhubSchema();
        $this->createIntegrationhubSchema();

        foreach (self::EXTRA_MIGRATIONS as $migration) {
            (require base_path($migration))->up();
        }

        $this->dropSecurityAlertSchema();
        $this->createSecurityAlertSchema();
    }

    protected function tearDown(): void
    {
        foreach (self::EXTRA_TABLES as $table) {
            Schema::dropIfExists($table);
        }

        $this->dropSecurityAlertSchema();
        $this->dropIntegrationhubSchema();

        parent::tearDown();
    }

    public function test_un_endpoint_que_no_responde_no_detiene_al_que_llama_y_avisa(): void
    {
        Queue::fake();

        SecurityAlertRecipient::create(['name' => 'Ops', 'chat_id' => '123', 'is_active' => true]);

        // Puerto cerrado: la llamada falla igual que con un servicio externo caído.
        $integration = $this->makeIntegration(['name' => 'Chatlevel', 'url_base' => 'http://127.0.0.1:1']);
        $endpoint = $this->makeEndpoint($integration, ['name' => 'create_contact']);

        $response = $this->callEndpoint($integration, $endpoint);

        // Se responde el fallo, no se lanza: el flujo que llamó sigue su camino.
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(500, (int) $response->getData(true)['status_code']);

        // El intento queda registrado en el Historial de Integrationhub.
        $log = IntegrationExecLog::query()->first();
        $this->assertNotNull($log, 'El fallo no quedó en el Historial de Integrationhub.');
        $this->assertSame('failed', $log->status);

        // Y el fallo llega al canal de alertas.
        $this->assertAlertPushed('create_contact');

        $error = SecurityErrorLog::query()->first();
        $this->assertSame('ERROR', $error?->level, 'El fallo del endpoint no llegó al visor de errores.');
    }

    public function test_un_endpoint_mal_configurado_tampoco_lanza_y_avisa(): void
    {
        Queue::fake();

        SecurityAlertRecipient::create(['name' => 'Ops', 'chat_id' => '123', 'is_active' => true]);

        $integration = $this->makeIntegration(['name' => 'Chatlevel', 'url_base' => 'http://127.0.0.1:1']);
        $endpoint = $this->makeEndpoint($integration, ['name' => 'create_contact', 'endpoint_path' => '/v1/contactos']);

        // El mapeo de Ruta URL exige {contact_id} dentro de la ruta y la ruta no
        // lo tiene: la llamada no puede ni armarse, y aun así no debe reventar.
        $endpoint->fieldMaps()->create([
            'field_key' => 'contact_id',
            'field_value' => '999',
            'field_type' => 'static',
            'field_location' => 'path',
            'source_type' => 'static',
            'is_enabled' => true,
        ]);

        $response = $this->callEndpoint($integration, $endpoint);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('placeholder', (string) $response->getData(true)['message']);

        $this->assertAlertPushed('placeholder');
    }

    public function test_un_endpoint_que_ya_no_existe_avisa_sin_lanzar(): void
    {
        Queue::fake();

        SecurityAlertRecipient::create(['name' => 'Ops', 'chat_id' => '123', 'is_active' => true]);

        // Nadie configuró (o alguien borró) el endpoint: el job que lo usaba no
        // puede quedarse mudo por eso.
        $response = app(IntegrationhubController::class)->runEndpoint('n8n_post_negociacion', []);

        $this->assertSame(404, $response->getStatusCode());

        $this->assertAlertPushed('n8n_post_negociacion');
    }

    /**
     * Ejecuta el endpoint igual que lo hacen los jobs y los servicios del
     * sistema: por runEndpoint(), que es la puerta interna de Integrationhub.
     */
    private function callEndpoint(Integration $integration, \Modules\Integrationhub\Entities\IntegrationEndpoint $endpoint)
    {
        return app(IntegrationhubController::class)
            ->runEndpoint($endpoint->name, ['contact_id' => '999']);
    }

    /**
     * El aviso debe salir por Telegram con la marca del módulo y el detalle.
     */
    private function assertAlertPushed(string $detail): void
    {
        Queue::assertPushed(SendSecurityErrorAlert::class, function (SendSecurityErrorAlert $job) use ($detail) {
            return str_contains($job->text, 'Integrationhub')
                && str_contains($job->text, $detail);
        });
    }
}
