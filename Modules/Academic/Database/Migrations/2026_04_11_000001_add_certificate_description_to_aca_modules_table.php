<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aca_modules', function (Blueprint $table) {
            $table->text('certificate_description')->nullable()->comment('Descripción personalizada para el certificado del módulo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('aca_modules', function (Blueprint $table) {
            $table->dropColumn('certificate_description');
        });
    }
};