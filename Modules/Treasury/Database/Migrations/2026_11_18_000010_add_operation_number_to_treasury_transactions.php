<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * N° de operación del banco.
 *
 * Es el identificador que asigna el banco a cada movimiento. Sirve para
 * reconocer que una línea del extracto y un movimiento del libro son la misma
 * operación aunque la fecha o la referencia difieran. Es opcional: cuando el
 * extracto no lo trae, la conciliación sigue comparando por fecha y monto.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('treasury_transactions') || Schema::hasColumn('treasury_transactions', 'operation_number')) {
            return;
        }

        Schema::table('treasury_transactions', function (Blueprint $table) {
            $table->string('operation_number', 60)->nullable()->after('reference');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('treasury_transactions') || ! Schema::hasColumn('treasury_transactions', 'operation_number')) {
            return;
        }

        Schema::table('treasury_transactions', function (Blueprint $table) {
            $table->dropColumn('operation_number');
        });
    }
};
