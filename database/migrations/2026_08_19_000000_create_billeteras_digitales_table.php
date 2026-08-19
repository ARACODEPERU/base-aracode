<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billeteras_digitales', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable();
            $table->string('short_name');
            $table->string('full_name');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        $billeteras = [
            ['short_name' => 'YAPE', 'full_name' => 'Yape'],
            ['short_name' => 'PLIN', 'full_name' => 'Plin'],
            ['short_name' => 'TUNKI', 'full_name' => 'Tunki'],
            ['short_name' => 'LUKITA', 'full_name' => 'Lukita'],
            ['short_name' => 'AGIL', 'full_name' => 'Agil BanBif'],
            ['short_name' => 'BITEL WALLET', 'full_name' => 'Bitel Wallet'],
            ['short_name' => 'BIM', 'full_name' => 'BIM'],
            ['short_name' => 'PAGO EFECTIVO', 'full_name' => 'PagoEfectivo'],
        ];

        foreach ($billeteras as $b) {
            DB::table('billeteras_digitales')->insert(array_merge($b, ['status' => true]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('billeteras_digitales');
    }
};
