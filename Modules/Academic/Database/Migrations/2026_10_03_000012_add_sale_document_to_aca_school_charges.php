<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vinculo de cada cobro escolar con la venta (sales) y el comprobante
     * (sale_documents) emitido al pagarlo, para poder re-imprimirlo desde
     * la vista de cobros.
     *
     * Idempotente y sin eliminacion de tablas (skill no-drop-tables).
     */
    public function up(): void
    {
        if (! Schema::hasTable('aca_school_charges')) {
            return;
        }

        if (! Schema::hasColumn('aca_school_charges', 'sale_id')) {
            Schema::table('aca_school_charges', function (Blueprint $table) {
                $table->unsignedBigInteger('sale_id')->nullable()
                    ->comment('Venta (sales) generada al cobrar')->after('created_by');
            });
        }

        if (! Schema::hasColumn('aca_school_charges', 'sale_document_id')) {
            Schema::table('aca_school_charges', function (Blueprint $table) {
                $table->unsignedBigInteger('sale_document_id')->nullable()
                    ->comment('Comprobante (sale_documents) emitido')->after('sale_id');
            });
        }

        if (! $this->hasIndex('aca_school_charges', 'aca_school_charges_sale_document_id_index')) {
            Schema::table('aca_school_charges', function (Blueprint $table) {
                $table->index('sale_document_id');
            });
        }
    }

    public function down(): void
    {
        // Sin eliminacion de estructuras (skill no-drop-tables).
    }

    /**
     * Comprueba el índice de forma portable (antes consultaba
     * information_schema, que solo existe en MySQL).
     */
    private function hasIndex(string $table, string $index): bool
    {
        return Schema::hasTable($table) && Schema::hasIndex($table, $index);
    }
};
