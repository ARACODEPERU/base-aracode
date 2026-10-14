<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contexto completo de los errores avisados por Telegram.
 *
 * La alerta que llega al chat es un resumen corto (Telegram corta en 4096
 * caracteres), así que el detalle que hace falta para corregir el fallo se
 * guarda aquí: traza completa, contexto del log, petición (ruta, método,
 * usuario, IP, navegador), payload saneado, etc. El mensaje de Telegram lleva
 * un enlace a esta fila.
 *
 * `occurrences` y `last_seen_at` agrupan las repeticiones: un error que se
 * repite en ráfaga no crea una fila nueva ni vuelve a avisar, solo suma y
 * actualiza la última vez visto (así la tabla no crece de más).
 *
 * `fingerprint` es la huella (nivel + mensaje + archivo + línea) con la que se
 * reconoce una repetición. Con `retention_days` el comando programado va
 * borrando las filas viejas.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('security_error_logs')) {
            return;
        }

        Schema::create('security_error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 40)->index('security_error_logs_fingerprint_index');
            $table->string('level', 20);
            $table->text('message');
            $table->string('exception_class', 255)->nullable();
            $table->string('file', 500)->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->longText('trace')->nullable();
            $table->longText('context')->nullable();
            $table->string('environment', 60)->nullable();
            $table->string('url', 1000)->nullable();
            $table->string('method', 12)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_label', 255)->nullable();
            $table->string('ip', 60)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedInteger('occurrences')->default(1);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('created_at', 'security_error_logs_created_at_index');
            $table->index('level', 'security_error_logs_level_index');
            $table->index('last_seen_at', 'security_error_logs_last_seen_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_error_logs');
    }
};
