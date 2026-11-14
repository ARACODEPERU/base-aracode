<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade el tipo \"cash\" (Efectivo/Caja) a las cuentas de tesorería.
     *
     * La cuenta de Efectivo/Caja no está vinculada a banco ni a billetera:
     * recibe el dinero cobrado en efectivo. MercadoPago sigue siendo una
     * cuenta tipo \"wallet\", por eso se asegura que exista en el catálogo.
     */
    public function up(): void
    {
        // Laravel recrea la columna con el nuevo enum en cada motor (MySQL y SQLite)
        Schema::table('treasury_accounts', function (Blueprint $table) {
            $table->enum('type', ['bank', 'wallet', 'cash'])->comment('bank = cuenta bancaria, wallet = billetera digital, cash = efectivo/caja')->change();
        });

        // Catálogo de MercadoPago (solo si aún no existe)
        $existe = DB::table('billeteras_digitales')
            ->whereRaw('LOWER(full_name) = ?', ['mercadopago'])
            ->exists();

        if (! $existe) {
            DB::table('billeteras_digitales')->insert([
                'short_name' => 'MERCADOPAGO',
                'full_name' => 'MercadoPago',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Método de pago MercadoPago para el selector de factura/boleta (solo si falta)
        $metodoExiste = DB::table('payment_methods')
            ->whereRaw('LOWER(description) = ?', ['mercadopago'])
            ->exists();

        if (! $metodoExiste) {
            DB::table('payment_methods')->insert([
                'description' => 'MercadoPago',
                'number' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('treasury_accounts')->where('type', 'cash')->delete();

        Schema::table('treasury_accounts', function (Blueprint $table) {
            $table->enum('type', ['bank', 'wallet'])->comment('bank = cuenta bancaria, wallet = billetera digital')->change();
        });
    }
};
