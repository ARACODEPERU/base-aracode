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
            // Eliminar la relación de clave foránea con la tabla aca_courses
            $table->dropForeign(['course_id']);

            // Modificar la columna course_id para que sea nullable
            $table->unsignedBigInteger('course_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('aca_certificates_parameters', function (Blueprint $table) {
            // Agregar la relación de clave foránea con la tabla aca_courses
            $table->foreign('course_id')->references('id')->on('aca_courses');

            // Modificar la columna course_id para que no sea nullable
            $table->unsignedBigInteger('course_id')->nullable(false)->change();
        });
    }
};
