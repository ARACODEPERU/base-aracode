<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suscripciones recurrentes de MercadoPago (preapproval) por alumno.
 *
 * Una fila por alumno y plan: guarda el id del preapproval en MercadoPago, el
 * estado, la fecha del próximo cobro y el snapshot de datos fiscales con el
 * que se emitirán los comprobantes de los meses siguientes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aca_subscription_preapprovals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->index()->comment('Alumno suscrito');
            $table->unsignedBigInteger('subscription_id')->index()->comment('Plan de suscripción (aca_subscription_types)');
            $table->string('preapproval_id', 64)->nullable()->unique()->comment('Id del preapproval en MercadoPago');
            $table->string('preapproval_plan_id', 64)->nullable()->comment('Id del plan de MercadoPago usado, si aplica');
            $table->string('status', 20)->default('pending')->index()->comment('pending, authorized, paused, cancelled');
            $table->decimal('amount', 10, 2)->default(0)->comment('Monto autorizado por periodo');
            $table->string('currency_id', 3)->default('PEN');
            $table->unsignedSmallInteger('frequency')->default(1)->comment('Cada cuántos periodos cobra');
            $table->string('frequency_type', 10)->default('months')->comment('months, days');
            $table->unsignedTinyInteger('billing_day')->nullable()->comment('Día del mes de cobro');
            $table->date('start_date')->nullable()->comment('Primer día cubierto por el cobro');
            $table->date('next_payment_date')->nullable()->index()->comment('Próximo cobro esperado');
            $table->timestamp('last_charge_at')->nullable()->comment('Último cobro aprobado');
            $table->timestamp('cancelled_at')->nullable()->comment('Cancelación pedida por el alumno o el admin');
            $table->timestamp('last_synced_at')->nullable()->comment('Última sincronización con MercadoPago');
            $table->string('payer_email', 150)->nullable();
            $table->string('external_reference', 100)->nullable()->index()->comment('Referencia propia enviada a MercadoPago');
            $table->json('invoice_data')->nullable()->comment('Snapshot fiscal para emitir los comprobantes mensuales');
            $table->json('raw')->nullable()->comment('Última respuesta cruda de MercadoPago');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aca_subscription_preapprovals');
    }
};
