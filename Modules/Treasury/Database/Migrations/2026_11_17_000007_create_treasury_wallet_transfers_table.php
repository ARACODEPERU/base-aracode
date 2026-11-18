<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cola de transferencias programadas de una billetera digital a su banco
 * vinculado. Cada ingreso diferido crea una fila pendiente; el comando
 * treasury:run-wallet-transfers ejecuta las que ya vencieron.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('treasury_wallet_transfers')) {
            return;
        }

        Schema::create('treasury_wallet_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_billetera_id')->constrained('company_billeteras')->cascadeOnDelete();
            $table->foreignId('wallet_account_id')->constrained('treasury_accounts')->cascadeOnDelete()
                ->comment('Cuenta de Tesorería donde está el dinero mientras espera');
            $table->foreignId('bank_account_id')->constrained('treasury_accounts')->restrictOnDelete()
                ->comment('Cuenta de banco que recibe la transferencia');
            $table->foreignId('source_bridge_id')->nullable()->constrained('treasury_source_movements')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('PEN');
            $table->date('scheduled_date')->comment('Fecha hábil en que se ejecuta');
            $table->string('status', 20)->default('pending');
            $table->foreignId('treasury_transfer_id')->nullable()->constrained('treasury_transfers')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();

            // Un ingreso genera una sola transferencia, aunque se reintente.
            $table->unique('source_bridge_id', 'treasury_wallet_transfers_bridge_unique');
            $table->index(['status', 'scheduled_date'], 'treasury_wallet_transfers_status_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasury_wallet_transfers');
    }
};
