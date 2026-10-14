<?php

namespace Tests\Unit\Modules\Security\Concerns;

use Illuminate\Support\Facades\Schema;

/**
 * Esquema mínimo de las alertas de error del módulo Security para las pruebas.
 *
 * El suite no corre todas las migraciones de la aplicación sobre sqlite (hay
 * migraciones con esquema MySQL), así que las pruebas del módulo ejecutan solo
 * las migraciones reales que necesitan: destinatarios, ajustes y el contexto de
 * errores con sus columnas de retención.
 */
trait BuildsSecurityAlertSchema
{
    private const ALERT_MIGRATIONS = [
        'Modules/Security/Database/Migrations/2026_09_30_000030_create_security_alert_recipients_table.php',
        'Modules/Security/Database/Migrations/2026_09_30_000031_create_security_alert_settings_table.php',
        'Modules/Security/Database/Migrations/2026_10_13_000001_create_security_error_logs_table.php',
        'Modules/Security/Database/Migrations/2026_10_13_000002_add_security_error_log_settings.php',
    ];

    private function createSecurityAlertSchema(): void
    {
        foreach (self::ALERT_MIGRATIONS as $migration) {
            (require base_path($migration))->up();
        }
    }

    private function dropSecurityAlertSchema(): void
    {
        foreach (array_reverse(self::ALERT_MIGRATIONS) as $migration) {
            (require base_path($migration))->down();
        }

        Schema::dropIfExists('security_alert_settings');
        Schema::dropIfExists('security_alert_recipients');
        Schema::dropIfExists('security_error_logs');
    }
}
