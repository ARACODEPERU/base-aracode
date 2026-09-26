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
        // CREATE EVENT es exclusivo de MySQL: en sqlite (suite de tests con
        // RefreshDatabase) la migracion se omite para no romper el refill.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

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
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared("DROP EVENT IF EXISTS marcar_suscripciones_vencidas");
    }
};
