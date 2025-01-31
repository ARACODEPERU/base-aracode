<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            // Agregar la columna interspace_description
            $table->double('interspace_description', 3, 1)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            // Eliminar la columna interspace_description
            $table->dropColumn('interspace_description');
        });
    }
};
