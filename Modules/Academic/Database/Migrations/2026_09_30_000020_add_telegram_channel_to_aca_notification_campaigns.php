<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El canal Telegram no notifica por telefono sino por chat_id.
     *
     * Por eso el padron congelado guarda ademas el chat_id y el telefono deja de
     * ser obligatorio: un destinatario de Telegram puede no tener telefono
     * valido y, aun asi, querer recibir el aviso.
     *
     * Idempotente con hasColumn; el cambio de nulabilidad no lleva guarda
     * adicional porque reaplicarlo es inocuo.
     */
    public function up(): void
    {
        if (! Schema::hasTable('aca_notification_campaign_recipients')) {
            return;
        }

        if (! Schema::hasColumn('aca_notification_campaign_recipients', 'chat_id')) {
            Schema::table('aca_notification_campaign_recipients', function (Blueprint $table) {
                $table->string('chat_id', 32)->nullable()->after('phone')
                    ->comment('Chat de Telegram del destinatario (canal telegram)');
            });
        }

        if (Schema::hasColumn('aca_notification_campaign_recipients', 'phone')) {
            Schema::table('aca_notification_campaign_recipients', function (Blueprint $table) {
                $table->string('phone', 20)->nullable()
                    ->comment('Telefono normalizado en E.164 sin signo + (null en Telegram)')
                    ->change();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('aca_notification_campaign_recipients')) {
            return;
        }

        if (Schema::hasColumn('aca_notification_campaign_recipients', 'chat_id')) {
            Schema::table('aca_notification_campaign_recipients', function (Blueprint $table) {
                $table->dropColumn('chat_id');
            });
        }

        // Volver a NOT NULL solo si no quedaron filas sin telefono: de lo
        // contrario la migracion fallaria y perderiamos el dato.
        $hasNullPhone = DB::table('aca_notification_campaign_recipients')->whereNull('phone')->exists();

        if (! $hasNullPhone && Schema::hasColumn('aca_notification_campaign_recipients', 'phone')) {
            Schema::table('aca_notification_campaign_recipients', function (Blueprint $table) {
                $table->string('phone', 20)->nullable(false)
                    ->comment('Telefono normalizado en E.164 sin signo +')
                    ->change();
            });
        }
    }
};
