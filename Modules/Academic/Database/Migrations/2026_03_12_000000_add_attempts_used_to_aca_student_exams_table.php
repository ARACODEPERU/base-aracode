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
        Schema::table('aca_student_exams', function (Blueprint $table) {
            $table->integer('attempts_used')->default(1)->after('status')->comment('Cantidad de intentos usados por el estudiante');
            $table->datetime('started_at')->nullable()->after('date_start')->comment('Hora de inicio del examen para el cronómetro');
            $table->integer('time_spent_seconds')->default(0)->after('finished_at')->comment('Tiempo total gastado en segundos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aca_student_exams', function (Blueprint $table) {
            $table->dropColumn(['attempts_used', 'started_at', 'time_spent_seconds']);
        });
    }
};
