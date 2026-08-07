<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('onli_carrito_abandonado', function (Blueprint $table) {
            if (Schema::hasColumn('onli_carrito_abandonado', 'referer')) {
                $table->text('referer')->nullable()->change();
            }
            if (Schema::hasColumn('onli_carrito_abandonado', 'landing_url')) {
                $table->text('landing_url')->nullable()->change();
            }
        });
    }

    public function down()
    {
        // OJO: revertir a VARCHAR(255) falla si ya existen filas con referer/landing_url > 255 chars.
        Schema::table('onli_carrito_abandonado', function (Blueprint $table) {
            if (Schema::hasColumn('onli_carrito_abandonado', 'referer')) {
                $table->string('referer')->nullable()->change();
            }
            if (Schema::hasColumn('onli_carrito_abandonado', 'landing_url')) {
                $table->string('landing_url')->nullable()->change();
            }
        });
    }
};
