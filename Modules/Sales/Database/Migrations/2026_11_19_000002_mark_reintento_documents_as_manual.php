<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los documentos que quedaron con la cola automática antigua pasan a
     * reenvío manual: sin próximo intento y sin que ningún proceso los tome.
     */
    public function up(): void
    {
        if (
            ! Schema::hasColumn('sale_documents', 'sunat_send_status')
            || ! Schema::hasColumn('sale_documents', 'sunat_next_retry_at')
        ) {
            return;
        }

        DB::table('sale_documents')
            ->where('sunat_send_status', 'reintento')
            ->update([
                'sunat_send_status' => 'manual',
                'sunat_next_retry_at' => null,
            ]);
    }

    /**
     * No se revierte: volver a la cola automática reenviaría comprobantes sin
     * intervención del usuario.
     */
    public function down(): void
    {
    }
};
