<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajustes del contexto de errores (mismos ajustes de las alertas).
 *
 * store_context   guarda el detalle completo del error para verlo por enlace.
 * retention_days  días que se conservan los errores guardados (0 = no borrar).
 * purge_enabled   borrado automático diario de los errores vencidos.
 *
 * Los valores por defecto conservan el comportamiento anterior a la migración
 * (avisar) y añaden el guardado con 30 días de retención.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('security_alert_settings')) {
            return;
        }

        Schema::table('security_alert_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('security_alert_settings', 'store_context')) {
                $table->boolean('store_context')->default(true)->after('cooldown_minutes');
            }

            if (! Schema::hasColumn('security_alert_settings', 'retention_days')) {
                $table->unsignedInteger('retention_days')->default(30)->after('store_context');
            }

            if (! Schema::hasColumn('security_alert_settings', 'purge_enabled')) {
                $table->boolean('purge_enabled')->default(true)->after('retention_days');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('security_alert_settings')) {
            return;
        }

        Schema::table('security_alert_settings', function (Blueprint $table) {
            $table->dropColumn(['store_context', 'retention_days', 'purge_enabled']);
        });
    }
};
