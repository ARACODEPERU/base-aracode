<?php

namespace Tests\Unit\Modules\Integrationhub\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Esquema mínimo para las pruebas del bot de Telegram.
 *
 * Igual que sus pares de Integrationhub: no se pueden correr todas las
 * migraciones de la aplicación sobre sqlite, así que aquí se ejecutan solo las
 * migraciones reales que toca el bot (parámetros del sistema, integraciones,
 * endpoints, field maps, bitácora de errores y las tablas nuevas de Telegram),
 * más una tabla `people` mínima: el saludo de bienvenida del bot resuelve el
 * nombre de la persona y esa consulta debe funcionar como en producción.
 */
trait BuildsTelegramBotSchema
{
    private const TELEGRAM_MIGRATIONS = [
        // Parametros del sistema (SC-00002 = token del bot).
        'database/migrations/2023_07_07_172256_create_parameters_table.php',
        'database/migrations/2026_09_30_000001_add_telegram_bot_token_parameter.php',

        // Esquema minimo de Integrationhub.
        'Modules/Integrationhub/Database/Migrations/2026_01_01_000001_create_integrations_table.php',
        'Modules/Integrationhub/Database/Migrations/2026_01_01_000003_create_integration_endpoints_table.php',
        'Modules/Integrationhub/Database/Migrations/2026_01_01_000005_create_integration_field_maps_table.php',
        'Modules/Integrationhub/Database/Migrations/2026_04_29_000001_add_field_location_to_integration_field_maps_table.php',
        'Modules/Integrationhub/Database/Migrations/2026_05_15_000001_add_is_required_to_integration_field_maps_table.php',
        'Modules/Integrationhub/Database/Migrations/2026_06_05_000001_create_integration_errors_table.php',
        'Modules/Integrationhub/Database/Migrations/2026_06_05_000002_add_source_to_integration_errors_table.php',

        // Telegram.
        'Modules/Integrationhub/Database/Migrations/2026_09_30_000010_create_integration_telegram_contacts_table.php',
        'Modules/Integrationhub/Database/Migrations/2026_09_30_000011_create_telegram_bot_integration.php',
    ];

    private const TELEGRAM_TABLES = [
        'people',
        'integration_field_maps',
        'integration_endpoints',
        'integrations',
        'integration_telegram_contacts',
        'integration_errors',
        'parameters',
    ];

    private function createTelegramSchema(): void
    {
        foreach (self::TELEGRAM_MIGRATIONS as $migration) {
            (require base_path($migration))->up();
        }

        $this->createPeopleTable();
    }

    /**
     * Tabla `people` mínima: el bot saluda por el nombre de la persona, así que
     * la relación no puede quedar sin tabla.
     */
    private function createPeopleTable(): void
    {
        if (Schema::hasTable('people')) {
            return;
        }

        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('short_name')->nullable();
            $table->string('full_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Vuelve a ejecutar una migración concreta (para probar la idempotencia).
     */
    private function rerunTelegramMigration(string $migration): void
    {
        (require base_path($migration))->up();
    }

    private function dropTelegramSchema(): void
    {
        // Se tira por tablas en vez de correr los down() de las migraciones:
        // los down() de ALTER fallan si la tabla no existe (primera corrida), y
        // lo que se prueban son los up() reales.
        foreach (self::TELEGRAM_TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
}
