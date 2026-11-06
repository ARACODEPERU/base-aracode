<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columnas opcionales para dejar trazable el resultado de la comunicación
     * de baja: qué respondió SUNAT, cómo se tradujo al usuario y si se obtuvo
     * el CDR. Todas son nullable, por lo que los registros existentes quedan
     * intactos (migración estrictamente aditiva: no se elimina ni se recrea
     * ninguna tabla).
     */
    public function up(): void
    {
        if (! Schema::hasTable('sale_low_communications')) {
            return;
        }

        Schema::table('sale_low_communications', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_low_communications', 'sunat_result')) {
                $table->string('sunat_result', 30)->nullable()
                    ->after('status')
                    ->comment('Resultado normalizado: aceptado, rechazado, en_proceso, sunat_no_disponible, ya_presentado, plazo_vencido, error_local');
            }

            if (! Schema::hasColumn('sale_low_communications', 'error_kind')) {
                $table->string('error_kind', 40)->nullable()
                    ->after('sunat_result')
                    ->comment('Categoría técnica del error (plazo_vencido, ticket_inexistente, transporte, ...)');
            }

            if (! Schema::hasColumn('sale_low_communications', 'alert_level')) {
                $table->string('alert_level', 20)->nullable()
                    ->after('error_kind')
                    ->comment('Nivel de la alerta mostrada al usuario: success, info, warning, error');
            }

            if (! Schema::hasColumn('sale_low_communications', 'alert_title')) {
                $table->string('alert_title', 150)->nullable()
                    ->after('alert_level');
            }

            if (! Schema::hasColumn('sale_low_communications', 'alert_message')) {
                $table->text('alert_message')->nullable()
                    ->after('alert_title')
                    ->comment('Mensaje entendible para el usuario (traducción del error de SUNAT)');
            }

            if (! Schema::hasColumn('sale_low_communications', 'recommended_action')) {
                $table->string('recommended_action', 40)->nullable()
                    ->after('alert_message')
                    ->comment('Acción sugerida: ninguna, reconsultar, reintentar, regenerar_baja, emitir_nota_credito, corregir_y_reenviar, contactar_soporte');
            }

            if (! Schema::hasColumn('sale_low_communications', 'cdr_code')) {
                $table->string('cdr_code', 10)->nullable()
                    ->after('recommended_action')
                    ->comment('Código devuelto en el CDR (0 = aceptado)');
            }

            if (! Schema::hasColumn('sale_low_communications', 'cdr_description')) {
                $table->text('cdr_description')->nullable()
                    ->after('cdr_code');
            }

            if (! Schema::hasColumn('sale_low_communications', 'last_checked_at')) {
                $table->timestamp('last_checked_at')->nullable()
                    ->after('cdr_description')
                    ->comment('Última vez que se consultó el ticket en SUNAT');
            }

            if (! Schema::hasColumn('sale_low_communications', 'check_attempts')) {
                $table->unsignedInteger('check_attempts')->nullable()->default(0)
                    ->after('last_checked_at')
                    ->comment('Número de consultas del ticket realizadas');
            }
        });
    }

    public function down(): void
    {
        // No se eliminan columnas: si hay que revertir, se dejan sin uso para no
        // perder información histórica en producción.
    }
};
