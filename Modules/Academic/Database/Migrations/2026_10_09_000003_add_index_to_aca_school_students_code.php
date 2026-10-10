<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La porteria busca al alumno por su codigo (el que va impreso en el QR del
     * carné) en cada escaneo, con la puerta llena. Sin indice esa busqueda
     * recorre toda la tabla, justo en el momento de mas carga.
     *
     * No es unique a proposito: student_code es nullable y el codigo se genera
     * por colegio, asi que la unicidad la garantiza el flujo de alta.
     */
    public function up(): void
    {
        Schema::table('aca_school_students', function (Blueprint $table) {
            $table->index(['school_id', 'student_code'], 'aca_school_students_school_code_index');
        });
    }

    public function down(): void
    {
        Schema::table('aca_school_students', function (Blueprint $table) {
            $table->dropIndex('aca_school_students_school_code_index');
        });
    }
};
