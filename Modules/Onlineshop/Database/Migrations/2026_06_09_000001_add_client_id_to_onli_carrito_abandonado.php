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
            if (!Schema::hasColumn('onli_carrito_abandonado', 'client_id')) {
                $table->string('client_id', 100)->nullable()->after('id');
            }

            // SHOW INDEXES es exclusivo de MySQL; en sqlite (suite de tests)
            // se consultan los indices de forma portable con Schema::getIndexes.
            $indexes = DB::getDriverName() === 'mysql'
                ? collect(DB::select('SHOW INDEXES FROM onli_carrito_abandonado'))
                    ->pluck('Key_name')
                    ->toArray()
                : array_column(Schema::getIndexes('onli_carrito_abandonado'), 'name');

            if (!in_array('onli_carrito_abandonado_client_id_index', $indexes)) {
                $table->index('client_id');
            }
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
