<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las guías que quedaron marcadas para la cola automática antigua pasan a
     * reenvío manual: la cola ya no existe y ningún proceso las debe tomar.
     */
    public function up(): void
    {
        if (
            ! Schema::hasTable('guias_remision')
            || ! Schema::hasColumn('guias_remision', 'sunat_send_status')
        ) {
            return;
        }

        DB::table('guias_remision')
            ->where('sunat_send_status', 'reintento')
            ->update(['sunat_send_status' => 'manual']);
    }

    /**
     * No se revierte: volver a la cola automática reenviaría guías sin
     * intervención del usuario.
     */
    public function down(): void
    {
    }
};
