<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Premium VIP Mensual
        DB::table('aca_subscription_types')->insert([
            'title' => 'Premium VIP',
            'description' => 'Acceso ilimitado a todos los cursos, webinars y programas de especialización. En programas de especialización, el acceso a Zoom/Meet está restringido.',
            'details' => json_encode([
                ['label' => 'Acceso a todos los cursos'],
                ['label' => 'Acceso a webinars'],
                ['label' => 'Acceso a programas de especialización (sin Zoom/Meet)'],
                ['label' => 'Contenido bajo demanda'],
            ]),
            'prices' => json_encode([
                ['currency' => 'PEN', 'amount' => 200, 'detail' => 'Mensual']
            ]),
            'status' => true,
            'period' => 'Mensual',
            'order_number' => 10,
            'usine' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Premium VIP Anual
        DB::table('aca_subscription_types')->insert([
            'title' => 'Premium VIP',
            'description' => 'Acceso ilimitado a todos los cursos, webinars y programas de especialización. En programas de especialización, el acceso a Zoom/Meet está restringido.',
            'details' => json_encode([
                ['label' => 'Acceso a todos los cursos'],
                ['label' => 'Acceso a webinars'],
                ['label' => 'Acceso a programas de especialización (sin Zoom/Meet)'],
                ['label' => 'Contenido bajo demanda'],
            ]),
            'prices' => json_encode([
                ['currency' => 'PEN', 'amount' => 2500, 'detail' => 'Anual']
            ]),
            'status' => true,
            'period' => 'Anual',
            'order_number' => 11,
            'usine' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::table('aca_subscription_types')
            ->where('title', 'Premium VIP')
            ->delete();
    }
};
