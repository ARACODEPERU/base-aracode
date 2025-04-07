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
        Schema::table('aca_short_videos', function (Blueprint $table) {
            $table->bigInteger('number_views')->default(0)->comment('cantidad de vistas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_short_videos', function (Blueprint $table) {
            $table->dropColumn('number_views');
        });
    }
};
