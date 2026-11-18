<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Días hábiles que tarda la billetera en transferir su dinero al banco.
 *
 * 0 = transferencia inmediata (el ingreso se registra en el banco).
 * 1 o más = el dinero queda en la billetera y se programa su traslado al
 * banco para la fecha acordada, contando días hábiles.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('company_billeteras', 'bank_transfer_days')) {
            Schema::table('company_billeteras', function (Blueprint $table) {
                $table->unsignedTinyInteger('bank_transfer_days')
                    ->default(0)
                    ->after('bank_account_id')
                    ->comment('Días hábiles para transferir al banco; 0 = inmediata');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('company_billeteras', 'bank_transfer_days')) {
            Schema::table('company_billeteras', function (Blueprint $table) {
                $table->dropColumn('bank_transfer_days');
            });
        }
    }
};
