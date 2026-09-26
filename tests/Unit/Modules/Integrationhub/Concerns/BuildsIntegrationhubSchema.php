<?php

namespace Tests\Unit\Modules\Integrationhub\Concerns;

use Illuminate\Support\Facades\Schema;
use Modules\Integrationhub\Entities\Integration;
use Modules\Integrationhub\Entities\IntegrationEndpoint;
use Modules\Integrationhub\Entities\IntegrationSchedule;

/**
 * Esquema mínimo de Integrationhub para las pruebas.
 *
 * Igual que el suite del Modo Super Editor: no se puede correr todas las
 * migraciones de la aplicación sobre sqlite, así que aquí se ejecutan solo las
 * migraciones reales del módulo que toca el ciclo de las programaciones
 * (integraciones, endpoints, programaciones y bitácora de errores).
 */
trait BuildsIntegrationhubSchema
{
    private const MODULE_MIGRATIONS = [
        'Modules/Integrationhub/Database/Migrations/2026_01_01_000001_create_integrations_table.php',
        'Modules/Integrationhub/Database/Migrations/2026_01_01_000003_create_integration_endpoints_table.php',
        'Modules/Integrationhub/Database/Migrations/2026_01_01_000008_create_integration_schedules_table.php',
        'Modules/Integrationhub/Database/Migrations/2026_05_15_000003_add_endpoint_and_payload_to_integration_schedules_table.php',
        'Modules/Integrationhub/Database/Migrations/2026_05_16_000004_add_target_type_to_integration_schedules_table.php',
        'Modules/Integrationhub/Database/Migrations/2026_06_05_000001_create_integration_errors_table.php',
        'Modules/Integrationhub/Database/Migrations/2026_06_05_000002_add_source_to_integration_errors_table.php',
        'Modules/Integrationhub/Database/Migrations/2026_09_26_000001_add_status_to_integration_schedules_table.php',
    ];

    private function createIntegrationhubSchema(): void
    {
        foreach (self::MODULE_MIGRATIONS as $migration) {
            (require base_path($migration))->up();
        }
    }

    private function dropIntegrationhubSchema(): void
    {
        // Se tira por tablas en vez de correr los down() de las migraciones:
        // los down() de ALTER fallan si la tabla no existe (primera corrida), y
        // lo que se prueban son los up() reales del módulo.
        foreach (['integration_errors', 'integration_schedules', 'integration_endpoints', 'integrations'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function makeIntegration(array $attributes = []): Integration
    {
        return Integration::create(array_merge([
            'name' => 'API de prueba',
            'url_base' => 'https://api.example.com',
            'execution_type' => 'scheduled',
            'is_active' => true,
            'config' => ['timeout' => 5],
        ], $attributes));
    }

    private function makeEndpoint(Integration $integration, array $attributes = []): IntegrationEndpoint
    {
        return $integration->endpoints()->create(array_merge([
            'name' => 'enviar_datos',
            'endpoint_path' => '/v1/datos',
            'http_method' => 'POST',
            'body_type' => 'json',
            'is_active' => true,
        ], $attributes));
    }

    private function makeSchedule(Integration $integration, array $attributes = []): IntegrationSchedule
    {
        return $integration->schedules()->create(array_merge([
            'target_type' => 'integration_endpoint',
            'cron_expression' => '* * * * *',
            'payload' => [],
            'is_active' => true,
            'next_execution_at' => now()->subMinute(),
        ], $attributes));
    }
}
