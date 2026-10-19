<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Interruptores por tipo de aviso en las alertas del sistema.
 *
 * `enabled` sigue siendo el interruptor general: si está apagado no sale nada.
 * Con estas columnas cada tipo de aviso se puede silenciar por separado sin
 * tocar los demás:
 *
 *   errors_enabled    avisos de fallos registrados en los logs.
 *   contacts_enabled  avisos de mensajes del formulario de contacto.
 *
 * Los valores por defecto conservan el comportamiento anterior a la migración
 * (ambos tipos activos), así ninguna instalación queda en silencio al desplegar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('security_alert_settings')) {
            return;
        }

        Schema::table('security_alert_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('security_alert_settings', 'errors_enabled')) {
                $table->boolean('errors_enabled')->default(true)->after('enabled');
            }

            if (! Schema::hasColumn('security_alert_settings', 'contacts_enabled')) {
                $table->boolean('contacts_enabled')->default(true)->after('errors_enabled');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('security_alert_settings')) {
            return;
        }

        Schema::table('security_alert_settings', function (Blueprint $table) {
            $table->dropColumn(['errors_enabled', 'contacts_enabled']);
        });
    }
};
