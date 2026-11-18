<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relaciona un comprobante emitido desde Comercial con el contrato y la cuota
 * de su cronograma. Son solo referencias para mostrar el origen del cobro en
 * Tesorería: los importes y el cronograma siguen viviendo en Comercial.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sale_documents')) {
            return;
        }

        Schema::table('sale_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_documents', 'commercial_contract_id')) {
                $table->unsignedBigInteger('commercial_contract_id')->nullable()->index();
            }

            if (! Schema::hasColumn('sale_documents', 'commercial_contract_payment_id')) {
                $table->unsignedBigInteger('commercial_contract_payment_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sale_documents')) {
            return;
        }

        Schema::table('sale_documents', function (Blueprint $table) {
            if (Schema::hasColumn('sale_documents', 'commercial_contract_id')) {
                $table->dropColumn('commercial_contract_id');
            }

            if (Schema::hasColumn('sale_documents', 'commercial_contract_payment_id')) {
                $table->dropColumn('commercial_contract_payment_id');
            }
        });
    }
};
