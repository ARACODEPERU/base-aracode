<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columnas opcionales para el impacto operativo de las notas de crédito:
     * reingreso de mercadería y ajuste de caja. Todo es nullable/por defecto 0,
     * por lo que los comprobantes existentes quedan intactos. No se elimina ni
     * se recrea ninguna tabla (migración estrictamente aditiva).
     */
    public function up(): void
    {
        if (Schema::hasTable('sale_documents')) {
            Schema::table('sale_documents', function (Blueprint $table) {
                if (! Schema::hasColumn('sale_documents', 'restock_inventory')) {
                    $table->boolean('restock_inventory')->nullable()->default(false)
                        ->comment('La nota de credito reingresa mercaderia al almacen');
                }

                if (! Schema::hasColumn('sale_documents', 'restock_applied_at')) {
                    $table->timestamp('restock_applied_at')->nullable()
                        ->comment('Momento en que se genero el reingreso de stock (idempotencia)');
                }

                if (! Schema::hasColumn('sale_documents', 'cash_adjustment_amount')) {
                    $table->decimal('cash_adjustment_amount', 14, 2)->nullable()
                        ->comment('Parte de la nota pagada en efectivo que debe ajustarse en caja');
                }

                if (! Schema::hasColumn('sale_documents', 'cash_adjustment_mode')) {
                    $table->string('cash_adjustment_mode', 20)->nullable()
                        ->comment('caja_abierta | caja_cerrada | sin_caja | no_aplica');
                }

                if (! Schema::hasColumn('sale_documents', 'cash_adjustment_petty_cash_id')) {
                    $table->unsignedBigInteger('cash_adjustment_petty_cash_id')->nullable()
                        ->comment('Caja donde se registro el egreso del ajuste');
                }

                if (! Schema::hasColumn('sale_documents', 'cash_adjustment_applied_at')) {
                    $table->timestamp('cash_adjustment_applied_at')->nullable()
                        ->comment('Momento en que se aplico el ajuste de caja (idempotencia)');
                }

                if (! Schema::hasColumn('sale_documents', 'cash_adjusted_externally')) {
                    $table->boolean('cash_adjusted_externally')->nullable()->default(false)
                        ->comment('El ajuste se registro como egreso en otra caja; no se vuelve a descontar en el reporte');
                }
            });
        }

        if (Schema::hasTable('sale_document_items')) {
            Schema::table('sale_document_items', function (Blueprint $table) {
                if (! Schema::hasColumn('sale_document_items', 'restock_quantity')) {
                    $table->decimal('restock_quantity', 12, 2)->nullable()
                        ->comment('Cantidad que reingresa al almacen por esta linea de la nota');
                }
            });
        }
    }

    public function down(): void
    {
        // No se eliminan columnas: se dejan sin uso para no perder información.
    }
};
