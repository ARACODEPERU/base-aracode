<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la columna exchange_rate a sale_documents para dejar trazable
     * el tipo de cambio usado en cada comprobante emitido en moneda extranjera.
     */
    public function up(): void
    {
        if (! Schema::hasTable('sale_documents')) {
            return;
        }

        if (! Schema::hasColumn('sale_documents', 'exchange_rate')) {
            Schema::table('sale_documents', function (Blueprint $table) {
                $table->decimal('exchange_rate', 10, 4)->nullable()
                    ->after('invoice_type_currency')
                    ->comment('Tipo de cambio aplicado cuando el comprobante no es en soles');
            });
        }
    }

    public function down(): void
    {
        // No se elimina la columna (regla del proyecto: evitar destructivos).
    }
};
