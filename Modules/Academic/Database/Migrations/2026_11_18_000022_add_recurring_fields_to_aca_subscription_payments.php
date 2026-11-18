<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trazabilidad de los cobros mensuales de una suscripción.
 *
 * Cada cobro autorizado por MercadoPago queda como un pago más del alumno, con
 * su referencia para no duplicar el ingreso ni el comprobante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_subscription_payments', function (Blueprint $table) {
            $table->string('authorized_payment_id', 64)
                ->nullable()
                ->unique()
                ->after('amount')
                ->comment('Id del cobro autorizado (invoice) en MercadoPago');

            $table->string('payment_id', 64)
                ->nullable()
                ->after('authorized_payment_id')
                ->comment('Id del pago de MercadoPago asociado al cobro');

            $table->string('preapproval_id', 64)
                ->nullable()
                ->index()
                ->after('payment_id')
                ->comment('Suscripción recurrente que originó el cobro');

            $table->string('status', 20)
                ->nullable()
                ->after('preapproval_id')
                ->comment('Estado del cobro en MercadoPago');

            $table->string('source', 20)
                ->default('manual')
                ->after('status')
                ->comment('manual o recurring');

            $table->timestamp('paid_at')
                ->nullable()
                ->after('source')
                ->comment('Fecha de acreditación del cobro');

            $table->unsignedBigInteger('mercadopago_event_id')
                ->nullable()
                ->after('paid_at')
                ->comment('Notificación que originó el registro');
        });
    }

    public function down(): void
    {
        Schema::table('aca_subscription_payments', function (Blueprint $table) {
            $table->dropUnique(['authorized_payment_id']);
            $table->dropIndex(['preapproval_id']);
            $table->dropColumn([
                'authorized_payment_id',
                'payment_id',
                'preapproval_id',
                'status',
                'source',
                'paid_at',
                'mercadopago_event_id',
            ]);
        });
    }
};
