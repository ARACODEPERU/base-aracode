<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estado de la cola de envío a SUNAT. Todas las columnas son nullable (o
     * con default 0), por lo que los comprobantes existentes no se alteran.
     * No se elimina ni se recrea ninguna tabla.
     */
    public function up(): void
    {
        if (! Schema::hasTable('sale_documents')) {
            return;
        }

        Schema::table('sale_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_documents', 'sunat_send_status')) {
                $table->string('sunat_send_status', 20)->nullable()
                    ->comment('pendiente | enviado | aceptado | rechazado | reintento | contingencia');
            }

            if (! Schema::hasColumn('sale_documents', 'sunat_retry_attempts')) {
                $table->unsignedInteger('sunat_retry_attempts')->nullable()->default(0)
                    ->comment('Intentos de envio realizados por la cola de reintento');
            }

            if (! Schema::hasColumn('sale_documents', 'sunat_last_attempt_at')) {
                $table->timestamp('sunat_last_attempt_at')->nullable();
            }

            if (! Schema::hasColumn('sale_documents', 'sunat_next_retry_at')) {
                $table->timestamp('sunat_next_retry_at')->nullable()
                    ->comment('Proximo intento programado (backoff exponencial)');
            }

            if (! Schema::hasColumn('sale_documents', 'sunat_last_error')) {
                $table->text('sunat_last_error')->nullable()
                    ->comment('Detalle tecnico del ultimo fallo de comunicacion');
            }

            if (! Schema::hasColumn('sale_documents', 'sunat_response_kind')) {
                $table->string('sunat_response_kind', 30)->nullable()
                    ->comment('timeout | curl | soap | http | sunat_no_disponible | certificado | negocio');
            }
        });
    }

    public function down(): void
    {
        // No se eliminan columnas: se dejan sin uso para no perder información.
    }
};
