<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('onli_carrito_abandonado', function (Blueprint $table) {
            if (!Schema::hasColumn('onli_carrito_abandonado', 'paid')) {
                $table->boolean('paid')->default(false)->after('notification_count');
            }

            $indexes = collect(DB::select('SHOW INDEXES FROM onli_carrito_abandonado'))
                ->pluck('Key_name')
                ->toArray();

            if (!in_array('onli_carrito_abandonado_paid_index', $indexes)) {
                $table->index('paid');
            }
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
