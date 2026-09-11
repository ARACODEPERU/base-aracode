<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cuentas de tesorería unificadas: bancos y billeteras digitales.
     * Cada cuenta apunta a su registro de origen (bank_accounts o company_billeteras)
     * y guarda el saldo inicial anterior al sistema para el cálculo de saldo.
     *
     * La coherencia type <-> FK (bank exige bank_account_id, wallet exige
     * company_billetera_id) se valida en TreasuryAccount (capa de aplicación)
     * para mantener compatibilidad con MySQL/MariaDB antiguos.
     */
    public function up(): void
    {
        Schema::create('treasury_accounts', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['bank', 'wallet'])->comment('bank = cuenta bancaria, wallet = billetera digital');
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('company_billetera_id')->nullable()->constrained('company_billeteras')->nullOnDelete();
            $table->string('label')->nullable()->comment('Nombre visible editable (si se omite, se deduce de la cuenta vinculada)');
            $table->string('currency_type_id', 3)->default('PEN')->comment('Moneda de la cuenta');
            $table->decimal('opening_balance', 12, 2)->default(0)->comment('Saldo inicial antes de empezar a registrar en el sistema');
            $table->date('opening_balance_date')->nullable()->comment('Fecha de corte del saldo inicial');
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasury_accounts');
    }
};
