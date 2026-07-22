<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('onli_carrito_abandonado', 'last_success_at')) {
            Schema::table('onli_carrito_abandonado', function (Blueprint $table) {
                $table->timestamp('last_success_at')->nullable()->after('notification_count');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('onli_carrito_abandonado', 'last_success_at')) {
            Schema::table('onli_carrito_abandonado', function (Blueprint $table) {
                $table->dropColumn('last_success_at');
            });
        }
    }
};
