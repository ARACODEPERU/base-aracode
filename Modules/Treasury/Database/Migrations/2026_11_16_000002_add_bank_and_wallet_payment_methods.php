<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Treasury\Services\PaymentMethodCatalogService;

/**
 * El desplegable de método de pago (ventas, cuotas, tesorería) solo mostraba
 * Efectivo, Yape, Plin, Transferencia BCP/BBVA, Mercadopago y Giro.
 *
 * Esta migración completa el catálogo con las cuentas bancarias y billeteras
 * digitales activas de la empresa, sin duplicar lo que ya existe:
 *  - Una cuenta bancaria reutiliza el método que menciona a su banco
 *    ("Transferencia BCP") y, si no hay, crea uno con el nombre de la cuenta.
 *  - Una billetera crea su método con el nombre de la billetera ("Culqi").
 * Es idempotente: si el método ya existe, no hace nada.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_methods')) {
            return;
        }

        app(PaymentMethodCatalogService::class)->ensureForAccounts();
    }

    /**
     * No se eliminan métodos: pueden estar referenciados por ventas y cobros.
     */
    public function down(): void
    {
        //
    }
};
