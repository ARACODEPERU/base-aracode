<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marcas de notificación de los cobros recurrentes.
 *
 * Evitan repetir el correo cuando MercadoPago reenvía la misma notificación o
 * el webhook se reintenta; un cobro exitoso limpia las marcas de fallo para
 * que el siguiente incidente vuelva a avisarse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_subscription_preapprovals', function (Blueprint $table) {
            $table->timestamp('first_failure_notified_at')
                ->nullable()
                ->after('last_synced_at')
                ->comment('Correo del primer intento de cobro fallido');

            $table->timestamp('grace_expired_notified_at')
                ->nullable()
                ->after('first_failure_notified_at')
                ->comment('Correo de suspensión al terminar los días de gracia');
        });

        Schema::table('aca_subscription_payments', function (Blueprint $table) {
            $table->timestamp('renewal_notified_at')
                ->nullable()
                ->after('paid_at')
                ->comment('Correo de renovación enviado por este cobro');
        });
    }

    public function down(): void
    {
        Schema::table('aca_subscription_payments', function (Blueprint $table) {
            $table->dropColumn('renewal_notified_at');
        });

        Schema::table('aca_subscription_preapprovals', function (Blueprint $table) {
            $table->dropColumn(['first_failure_notified_at', 'grace_expired_notified_at']);
        });
    }
};
