<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deja lista la integración de MercadoPago en IntegrationHub para administrar
 * el secreto del webhook desde el panel, sin tocar el .env.
 *
 * El valor se registra vacío: se completa en IntegrationHub → MercadoPago →
 * campo webhook_secret. Mientras esté vacío, la aplicación usa
 * MERCADOPAGO_WEBHOOK_SECRET como respaldo.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('integrations') || ! Schema::hasTable('integration_auth')) {
            return;
        }

        $integrationId = DB::table('integrations')
            ->whereRaw('LOWER(name) = ?', ['mercadopago'])
            ->value('id');

        if (! $integrationId) {
            $integrationId = DB::table('integrations')->insertGetId([
                'name' => 'MercadoPago',
                'url_base' => 'https://api.mercadopago.com',
                'description' => 'Credenciales de la cuenta de MercadoPago usadas por cobros online y suscripciones.',
                'execution_type' => 'manual',
                'is_active' => 1,
                'config' => json_encode(['timeout' => 30]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $exists = DB::table('integration_auth')
            ->where('integration_id', $integrationId)
            ->whereRaw('LOWER(field_name) = ?', ['webhook_secret'])
            ->exists();

        if (! $exists) {
            DB::table('integration_auth')->insert([
                'integration_id' => $integrationId,
                'field_name' => 'webhook_secret',
                'field_value' => null,
                'field_type' => 'password',
                'auth_location' => 'header',
                'is_enabled' => 1,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('integrations') || ! Schema::hasTable('integration_auth')) {
            return;
        }

        $integrationId = DB::table('integrations')
            ->whereRaw('LOWER(name) = ?', ['mercadopago'])
            ->value('id');

        if (! $integrationId) {
            return;
        }

        DB::table('integration_auth')
            ->where('integration_id', $integrationId)
            ->whereRaw('LOWER(field_name) = ?', ['webhook_secret'])
            ->delete();

        $tieneMasDatos = DB::table('integration_auth')->where('integration_id', $integrationId)->exists()
            || DB::table('integration_endpoints')->where('integration_id', $integrationId)->exists();

        if (! $tieneMasDatos) {
            DB::table('integrations')->where('id', $integrationId)->delete();
        }
    }
};
