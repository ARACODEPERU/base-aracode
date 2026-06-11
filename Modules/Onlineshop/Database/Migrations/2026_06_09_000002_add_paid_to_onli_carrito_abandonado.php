<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('onli_carrito_abandonado', function (Blueprint $table) {
            $table->boolean('paid')->default(false)->after('notification_count');
            $table->index('paid');
        });
    }

    public function down()
    {
        Schema::table('onli_carrito_abandonado', function (Blueprint $table) {
            $table->dropIndex(['paid']);
            $table->dropColumn('paid');
        });
    }
};
