<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reserva de cobros para Sales e índices del listado.
 *
 * `billed_at` guarda cuándo un cobro pasó a `billed` (en preparación para
 * emitir el documento de venta). Con esa marca el listado libera las reservas
 * vencidas, así un borrador abandonado no deja el cobro escondido para siempre
 * y el mismo cobro no se puede facturar dos veces.
 *
 * Los índices siguen el orden real de las consultas de la pantalla: el listado
 * filtra por estado y documento y ordena por fecha de cobro, y la liberación de
 * reservas vencidas filtra por estado y marca.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heal_patient_charges', function (Blueprint $table) {
            $table->timestamp('billed_at')->nullable();
            $table->index(['status', 'sale_document_id', 'charged_at'], 'heal_patient_charges_billing_index');
            $table->index(['status', 'billed_at'], 'heal_patient_charges_reservation_index');
        });
    }

    public function down(): void
    {
        Schema::table('heal_patient_charges', function (Blueprint $table) {
            $table->dropIndex('heal_patient_charges_billing_index');
            $table->dropIndex('heal_patient_charges_reservation_index');
            $table->dropColumn('billed_at');
        });
    }
};
