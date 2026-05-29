<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE even_local_rentals MODIFY reservation_status ENUM('pending', 'confirmed', 'in_occupation', 'cancelled', 'completed') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::table('even_local_rentals')
            ->where('reservation_status', 'in_occupation')
            ->update(['reservation_status' => 'confirmed']);

        DB::statement("ALTER TABLE even_local_rentals MODIFY reservation_status ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending'");
    }
};
