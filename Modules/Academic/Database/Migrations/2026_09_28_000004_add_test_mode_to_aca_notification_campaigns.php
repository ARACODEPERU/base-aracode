<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Modo prueba de las campanas de notificaciones.
     *
     * Una campana de prueba se envia unicamente a los numeros que escribe el
     * administrador (con su codigo de pais, separados por coma) en lugar del
     * padron real de alumnos, para verificar el envio sin molestar a los
     * estudiantes. Como en ese caso el programa es opcional (solo aporta el
     * nombre del curso), course_id pasa a ser nullable.
     *
     * Se valida con hasColumn / hasTable para que sea seguro incluso si la
     * tabla se creo antes de existir estas columnas.
     */
    public function up(): void
    {
        if (! Schema::hasTable('aca_notification_campaigns')) {
            return;
        }

        if (! Schema::hasColumn('aca_notification_campaigns', 'is_test')) {
            Schema::table('aca_notification_campaigns', function (Blueprint $table) {
                $table->boolean('is_test')->default(false)->after('channel')
                    ->comment('true = envio de prueba a numeros escritos a mano');
            });
        }

        // course_id nullable: una campana de prueba puede lanzarse sin programa.
        Schema::table('aca_notification_campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable()
                ->comment('Programa de especializacion notificado (null en modo prueba)')
                ->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('aca_notification_campaigns')) {
            return;
        }

        if (Schema::hasColumn('aca_notification_campaigns', 'is_test')) {
            Schema::table('aca_notification_campaigns', function (Blueprint $table) {
                $table->dropColumn('is_test');
            });
        }

        Schema::table('aca_notification_campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('course_id')->nullable(false)
                ->comment('Programa de especializacion notificado')
                ->change();
        });
    }
};
