<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Comisiones por cuenta, cobros programados, transferencias entre cuentas
     * y el origen billetera de cada ingreso.
     *
     * Los índices únicos 1:1 (bank_account_id / company_billetera_id) NO se crean
     * aquí: primero hay que resolver los duplicados existentes.
     */
    public function up(): void
    {
        // Comisiones y programación por cuenta de tesorería
        Schema::table('treasury_accounts', function (Blueprint $table) {
            $table->decimal('fixed_fee', 12, 2)->default(0)->comment('Comisión fija por operación');
            $table->decimal('percent_fee', 8, 4)->default(0)->comment('Comisión porcentual (ej. 3.49 = 3.49 %)');
            $table->boolean('igv_applies')->default(false)->comment('Si el IGV se suma a la comisión');
            $table->string('commission_formula', 255)->nullable()->comment('Fórmula opcional con variables monto, fijo, porcentaje, igv');
            $table->boolean('schedule_enabled')->default(false);
            $table->unsignedTinyInteger('schedule_day')->nullable()->comment('Día del mes (1-31); si no existe, se usa el último día');
            $table->time('schedule_time')->nullable();
            $table->decimal('schedule_amount', 12, 2)->nullable()->comment('Monto del cargo programado');
            $table->string('schedule_concept', 150)->nullable();
        });

        // Origen billetera y desglose de comisiones en cada movimiento
        Schema::table('treasury_transactions', function (Blueprint $table) {
            $table->foreignId('source_wallet_account_id')->nullable()->after('treasury_account_id')
                ->constrained('treasury_accounts')->nullOnDelete()
                ->comment('Billetera por la que llegó el dinero (si aplica)');
            $table->decimal('fee_amount', 12, 2)->default(0);
            $table->decimal('igv_amount', 12, 2)->default(0);
            $table->decimal('itf_amount', 12, 2)->default(0);
            $table->foreignId('transfer_id')->nullable()->after('origin_id');
        });

        // Registro de cada cobro programado ejecutado (evita duplicados por periodo)
        Schema::create('treasury_scheduled_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasury_account_id')->constrained('treasury_accounts')->cascadeOnDelete();
            $table->string('period', 7)->comment('Periodo AAAA-MM de la ejecución');
            $table->foreignId('treasury_transaction_id')->nullable()->constrained('treasury_transactions')->nullOnDelete();
            $table->timestamp('executed_at');
            $table->timestamps();

            $table->unique(['treasury_account_id', 'period'], 'treasury_scheduled_runs_account_period_unique');
        });

        // Transferencias entre cuentas
        Schema::create('treasury_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_account_id')->constrained('treasury_accounts')->restrictOnDelete();
            $table->foreignId('to_account_id')->constrained('treasury_accounts')->restrictOnDelete();
            $table->date('transfer_date');
            $table->decimal('amount', 12, 2)->comment('Monto transferido (lo que sale de la cuenta origen antes de comisiones)');
            $table->decimal('from_fixed_fee', 12, 2)->default(0);
            $table->decimal('from_percent_fee', 8, 4)->default(0);
            $table->decimal('to_fixed_fee', 12, 2)->default(0);
            $table->decimal('to_percent_fee', 8, 4)->default(0);
            $table->boolean('itf_applies')->default(true);
            $table->decimal('itf_amount', 12, 2)->default(0);
            $table->decimal('net_received', 12, 2)->comment('Lo que realmente recibe la cuenta destino');
            $table->string('reference')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Comisiones guardadas por par origen-destino para proponerlas en la siguiente transferencia
        Schema::create('treasury_transfer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_account_id')->constrained('treasury_accounts')->cascadeOnDelete();
            $table->foreignId('to_account_id')->constrained('treasury_accounts')->cascadeOnDelete();
            $table->decimal('from_fixed_fee', 12, 2)->default(0);
            $table->decimal('from_percent_fee', 8, 4)->default(0);
            $table->decimal('to_fixed_fee', 12, 2)->default(0);
            $table->decimal('to_percent_fee', 8, 4)->default(0);
            // Check "transferencia de mi propia cuenta libre de ITF": se guarda la última elección del par
            $table->boolean('own_account_itf_free')->default(false);
            $table->timestamps();

            $table->unique(['from_account_id', 'to_account_id'], 'treasury_transfer_profiles_pair_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasury_transfer_profiles');
        Schema::dropIfExists('treasury_transfers');
        Schema::dropIfExists('treasury_scheduled_runs');

        Schema::table('treasury_transactions', function (Blueprint $table) {
            $table->dropForeign(['source_wallet_account_id']);
            $table->dropColumn(['source_wallet_account_id', 'fee_amount', 'igv_amount', 'itf_amount', 'transfer_id']);
        });

        Schema::table('treasury_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'fixed_fee', 'percent_fee', 'igv_applies', 'commission_formula',
                'schedule_enabled', 'schedule_day', 'schedule_time', 'schedule_amount', 'schedule_concept',
            ]);
        });
    }
};
