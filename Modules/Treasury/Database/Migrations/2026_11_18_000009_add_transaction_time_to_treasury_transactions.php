<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hora del movimiento bancario.
 *
 * Los extractos a veces distinguen dos operaciones del mismo día por la hora.
 * La columna es opcional: los movimientos que no la tienen siguen funcionando
 * igual y la conciliación compara por fecha, monto y sentido.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('treasury_transactions') || Schema::hasColumn('treasury_transactions', 'transaction_time')) {
            return;
        }

        Schema::table('treasury_transactions', function (Blueprint $table) {
            $table->time('transaction_time')->nullable()->after('transaction_date');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('treasury_transactions') || ! Schema::hasColumn('treasury_transactions', 'transaction_time')) {
            return;
        }

        Schema::table('treasury_transactions', function (Blueprint $table) {
            $table->dropColumn('transaction_time');
        });
    }
};
