<?php

namespace Tests\Unit\Modules\Security\Concerns;

use Illuminate\Support\Facades\Schema;

/**
 * Esquema mínimo de las alertas de error del módulo Security para las pruebas.
 *
 * El suite no corre todas las migraciones de la aplicación sobre sqlite (hay
 * migraciones con esquema MySQL), así que las pruebas del módulo ejecutan solo
 * las migraciones reales que necesitan: destinatarios y ajustes.
 */
trait BuildsSecurityAlertSchema
{
    private const ALERT_MIGRATIONS = [
        'Modules/Security/Database/Migrations/2026_09_30_000030_create_security_alert_recipients_table.php',
        'Modules/Security/Database/Migrations/2026_09_30_000031_create_security_alert_settings_table.php',
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
    }
}
