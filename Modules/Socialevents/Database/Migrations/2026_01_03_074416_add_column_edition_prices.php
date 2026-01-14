<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_editions', function (Blueprint $table) {
            $table->integer('yellow_price')->default(0)->comment('precio tarjeta amarilla');
            $table->integer('direct_red_price')->default(0)->comment('precio tarjeta roja directa');
            $table->integer('double_yellow_price')->default(0)->comment('precio tarjeta roja por doble amarilla');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_editions', function (Blueprint $table) {
            $table->dropColumn('double_yellow_price');
            $table->dropColumn('direct_red_price');
            $table->dropColumn('yellow_price');
        });
    }
};
