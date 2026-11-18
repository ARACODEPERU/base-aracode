<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modo prueba de los cobros recurrentes.
 *
 * - `aca_subscription_types.test_mode`: habilita el panel de pruebas del plan.
 * - `is_test` en preapprovals y pagos: permite borrar después solo lo generado
 *   por las pruebas, sin tocar suscripciones reales.
 * - `aca_subscription_test_logs`: bitácora de cada botón (cobros, correos,
 *   cancelaciones y limpieza), visible en la pantalla.
 * - Credenciales de prueba en IntegrationHub: test_access_token y
 *   test_public_key (nunca se usan las de producción en este modo).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aca_subscription_types') && ! Schema::hasColumn('aca_subscription_types', 'test_mode')) {
            Schema::table('aca_subscription_types', function (Blueprint $table) {
                $table->boolean('test_mode')->default(false)->after('recurring_enabled')
                    ->comment('Habilita el panel de pruebas de cobros recurrentes');
            });
        }

        if (Schema::hasTable('aca_subscription_preapprovals') && ! Schema::hasColumn('aca_subscription_preapprovals', 'is_test')) {
            Schema::table('aca_subscription_preapprovals', function (Blueprint $table) {
                $table->boolean('is_test')->default(false)->index()
                    ->comment('Suscripción creada por el modo prueba');
            });
        }

        if (Schema::hasTable('aca_subscription_payments') && ! Schema::hasColumn('aca_subscription_payments', 'is_test')) {
            Schema::table('aca_subscription_payments', function (Blueprint $table) {
                $table->boolean('is_test')->default(false)->index()
                    ->comment('Pago generado por el modo prueba');
            });
        }

        if (! Schema::hasTable('aca_subscription_test_logs')) {
            Schema::create('aca_subscription_test_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subscription_id')->index()->comment('Plan de suscripción');
                $table->string('preapproval_id', 64)->nullable()->index();
                $table->string('action', 40)->comment('created, renewal, webhook, cancelled, purge');
                $table->string('level', 20)->default('info')->comment('info, success, warning, error');
                $table->text('message');
                $table->json('context')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('integrations') && Schema::hasTable('integration_auth')) {
            $integrationId = DB::table('integrations')
                ->whereRaw('LOWER(name) = ?', ['mercadopago'])
                ->value('id');

            if ($integrationId) {
                $campos = [
                    ['test_access_token', 'password', 'Access token de PRUEBA (nunca el de producción).'],
                    ['test_public_key', 'text', 'Public key de PRUEBA, para generar el token de la tarjeta de prueba.'],
                ];

                foreach ($campos as $indice => [$nombre, $tipo, $ayuda]) {
                    $existe = DB::table('integration_auth')
                        ->where('integration_id', $integrationId)
                        ->where('field_name', $nombre)
                        ->exists();

                    if (! $existe) {
                        DB::table('integration_auth')->insert([
                            'integration_id' => $integrationId,
                            'field_name' => $nombre,
                            'field_value' => null,
                            'field_type' => $tipo,
                            'auth_location' => 'header',
                            'is_enabled' => 1,
                            'sort_order' => $indice + 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('aca_subscription_test_logs')) {
            Schema::dropIfExists('aca_subscription_test_logs');
        }

        if (Schema::hasTable('aca_subscription_payments') && Schema::hasColumn('aca_subscription_payments', 'is_test')) {
            Schema::table('aca_subscription_payments', function (Blueprint $table) {
                $table->dropColumn('is_test');
            });
        }

        if (Schema::hasTable('aca_subscription_preapprovals') && Schema::hasColumn('aca_subscription_preapprovals', 'is_test')) {
            Schema::table('aca_subscription_preapprovals', function (Blueprint $table) {
                $table->dropColumn('is_test');
            });
        }

        if (Schema::hasTable('aca_subscription_types') && Schema::hasColumn('aca_subscription_types', 'test_mode')) {
            Schema::table('aca_subscription_types', function (Blueprint $table) {
                $table->dropColumn('test_mode');
            });
        }

        if (Schema::hasTable('integrations') && Schema::hasTable('integration_auth')) {
            $integrationId = DB::table('integrations')
                ->whereRaw('LOWER(name) = ?', ['mercadopago'])
                ->value('id');

            if ($integrationId) {
                DB::table('integration_auth')
                    ->where('integration_id', $integrationId)
                    ->whereIn('field_name', ['test_access_token', 'test_public_key'])
                    ->delete();
            }
        }
    }
};
