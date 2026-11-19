<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los comprobantes que quedaron en cola antes de este cambio conservaron
     * el estado "Pendiente de Reintento". El estado visible debe ser
     * "Pendiente" para que el modal de edición lo muestre y se pueda reenviar;
     * la cola real sigue en sunat_send_status.
     */
    public function up(): void
    {
        if (! Schema::hasTable('sale_documents') || ! Schema::hasColumn('sale_documents', 'invoice_status')) {
            return;
        }

        DB::table('sale_documents')
            ->where('invoice_status', 'Pendiente de Reintento')
            ->update(['invoice_status' => 'Pendiente']);
    }

    public function down(): void
    {
        // Normalización de datos: no se revierte para no dejar estados ambiguos.
    }
};
