<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('aca_exams', function (Blueprint $table) {
            // Tiempo máximo permitido en minutos (ej: 120 para 2 horas)
            // Usamos integer para facilitar cálculos matemáticos después
            $table->integer('duration_minutes')->default(120)->after('attempts')->comment('Tiempo máximo permitido en el examen');
            $table->string('file_resolved_name')->nullable()->comment('nombre del archivo del examen resuelto');
            $table->string('file_resolved_path')->nullable()->comment('ruta del archivo del examen resuelto');
        });

        Schema::table('aca_student_exams', function (Blueprint $table) {
            // Cuándo inició el examen (fundamental para calcular el tiempo restante)
            $table->timestamp('started_at')->nullable()->comment('Cuándo inició el examen');

            // Cuándo terminó realmente
            $table->timestamp('finished_at')->nullable()->comment('Cuándo terminó realmente');

            // Tiempo total que le tomó al alumno (en segundos o minutos)
            // Guardarlo directamente evita recalcular cada vez en reportes
            $table->integer('time_spent_seconds')->default(0)->comment('Tiempo total que le tomó al alumno (en segundos o minutos)');

            // Estado para saber si se le cerró el tiempo automáticamente
            $table->boolean('is_timed_out')->default(false)->comment('Estado para saber si se le cerró el tiempo automáticamente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_student_exams', function (Blueprint $table) {
            $table->dropColumn('is_timed_out');
            $table->dropColumn('time_spent_seconds');
            $table->dropColumn('finished_at');
            $table->dropColumn('started_at');
        });
        Schema::table('aca_exams', function (Blueprint $table) {
            $table->dropColumn('duration_minutes');
        });
    }
};
