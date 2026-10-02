<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ajustes de las alertas de error (fila única).
 *
 * enabled        interruptor general: si está apagado no se envía ninguna alerta.
 * min_level      nivel mínimo del log que dispara un aviso (error por defecto).
 * cooldown_minutes ventana de anti-duplicados por huella de error.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('security_alert_settings')) {
            Schema::create('security_alert_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('enabled')->default(true);
                $table->string('min_level', 20)->default('error');
                $table->unsignedInteger('cooldown_minutes')->default(5);
                $table->timestamps();
            });
        }

        if (! DB::table('security_alert_settings')->exists()) {
            DB::table('security_alert_settings')->insert([
                'enabled' => true,
                'min_level' => 'error',
                'cooldown_minutes' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('security_alert_settings');
    }
};
