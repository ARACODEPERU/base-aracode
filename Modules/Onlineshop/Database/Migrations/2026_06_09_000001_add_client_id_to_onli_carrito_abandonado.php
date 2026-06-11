<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('onli_carrito_abandonado', function (Blueprint $table) {
            $table->string('client_id', 100)->nullable()->after('id');
            $table->string('phone', 20)->nullable()->change();
            $table->index('client_id');
        });
    }

    public function down()
    {
        Schema::table('onli_carrito_abandonado', function (Blueprint $table) {
            $table->dropIndex(['client_id']);
            $table->dropColumn('client_id');
            $table->string('phone', 20)->nullable(false)->change();
        });
    }
};
