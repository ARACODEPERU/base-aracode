<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deja identificable la integración de MercadoPago en el Centro de
 * Integraciones: es la que guarda el `webhook_secret` que valida las
 * notificaciones de suscripciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('integrations')) {
            return;
        }

        DB::table('integrations')
            ->whereRaw('LOWER(name) = ?', ['mercadopago'])
            ->update([
                'description' => 'Webhook de suscripciones de MercadoPago: aquí se guarda webhook_secret (firma secreta) y las credenciales de la cuenta.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('integrations')) {
            return;
        }

        DB::table('integrations')
            ->whereRaw('LOWER(name) = ?', ['mercadopago'])
            ->update([
                'description' => 'Credenciales de la cuenta de MercadoPago usadas por cobros online y suscripciones.',
                'updated_at' => now(),
            ]);
    }
};
