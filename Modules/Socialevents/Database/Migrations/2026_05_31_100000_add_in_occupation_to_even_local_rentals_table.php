<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MODIFY es exclusivo de MySQL (sqlite no altera tipos de columna).
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE even_local_rentals MODIFY reservation_status ENUM('pending', 'confirmed', 'in_occupation', 'cancelled', 'completed') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::table('even_local_rentals')
            ->where('reservation_status', 'in_occupation')
            ->update(['reservation_status' => 'confirmed']);

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE even_local_rentals MODIFY reservation_status ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending'");
    }
};
