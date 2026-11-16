<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los cobros en efectivo (método "Efectivo") buscan una cuenta de tesorería de
 * tipo "cash". Si la empresa nunca la creó, esos cobros quedaban ignorados.
 * Esta migración la crea una sola vez y no hace nada si ya existe.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('treasury_accounts')) {
            return;
        }

        $exists = DB::table('treasury_accounts')
            ->where('type', 'cash')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('treasury_accounts')->insert([
            'type' => 'cash',
            'bank_account_id' => null,
            'company_billetera_id' => null,
            'label' => 'Efectivo/Caja',
            'currency_type_id' => 'PEN',
            'opening_balance' => 0,
            'opening_balance_date' => null,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * No se elimina la cuenta: puede tener movimientos y su historial manda.
     */
    public function down(): void
    {
        //
    }
};
