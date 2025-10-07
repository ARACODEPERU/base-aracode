<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared("
            CREATE EVENT IF NOT EXISTS marcar_cuotas_y_documentos_vencidos
            ON SCHEDULE EVERY 1 DAY
            STARTS TIMESTAMP(CURRENT_DATE, '00:00:00')
            DO
            BEGIN
                -- 1) Actualizar cuotas vencidas
                UPDATE sale_document_quotas
                SET status = 'Vencido'
                WHERE due_date < CURDATE()
                  AND status IN ('Pendiente', 'Parcialmente Pagada', 'Amortizado')
                  AND balance > 0;

                -- 2) Marcar documentos relacionados
                UPDATE sale_documents sd
                JOIN sale_document_quotas q ON q.sale_document_id = sd.id
                SET sd.overdue_fee = 1
                WHERE q.due_date < CURDATE()
                  AND q.status = 'Vencido'
                  AND sd.overdue_fee = 0;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP EVENT IF EXISTS marcar_cuotas_y_documentos_vencidos");
    }
};
