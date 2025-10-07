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
            CREATE EVENT IF NOT EXISTS marcar_suscripciones_vencidas
            ON SCHEDULE EVERY 1 DAY
            STARTS TIMESTAMP(CURRENT_DATE, '00:00:00')
            DO
                UPDATE aca_student_subscriptions
                SET status = 0
                WHERE date_end < CURDATE()
                  AND status = 1
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP EVENT IF EXISTS marcar_suscripciones_vencidas");
    }
};
