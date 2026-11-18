<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Flags de recurrencia por plan de suscripción.
 *
 * Los planes existentes quedan en false: la venta de un solo periodo sigue
 * funcionando igual y la recurrencia se habilita plan por plan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_subscription_types', function (Blueprint $table) {
            $table->boolean('recurring_enabled')
                ->default(false)
                ->after('period')
                ->comment('Cobra automáticamente cada periodo con MercadoPago');

            $table->string('mercadopago_plan_id', 64)
                ->nullable()
                ->unique()
                ->after('recurring_enabled')
                ->comment('Plan de suscripción (preapproval_plan) en MercadoPago');
        });
    }

    public function down(): void
    {
        Schema::table('aca_subscription_types', function (Blueprint $table) {
            $table->dropUnique(['mercadopago_plan_id']);
            $table->dropColumn(['recurring_enabled', 'mercadopago_plan_id']);
        });
    }
};
