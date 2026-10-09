<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bloques del horario escolar: un bloque = una seccion, un area curricular,
     * el docente que la dicta, un dia de la semana y una hora de inicio y fin.
     *
     * Resuelve las tres preguntas del horario: la jornada de cada seccion (la
     * suma de sus bloques), que cursos lleva cada alumno (los bloques de su
     * seccion) y quien le dicta cada curso (teacher_person_id). Es ademas la
     * base del futuro registro de asistencia por hora del docente.
     * Idempotente y sin eliminacion de tablas (skill no-drop-tables).
     */
    public function up(): void
    {
        if (Schema::hasTable('aca_school_schedules')) {
            return;
        }

        Schema::create('aca_school_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('aca_schools')->cascadeOnDelete();
            $table->foreignId('year_id')->constrained('aca_school_years')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('aca_school_sections')->cascadeOnDelete();
            $table->unsignedBigInteger('area_id')->nullable()->comment('Area curricular del bloque');
            $table->unsignedBigInteger('teacher_person_id')->nullable()->comment('Docente que dicta el bloque');
            $table->unsignedTinyInteger('weekday')->comment('1=lunes ... 7=domingo');
            $table->time('start_time')->comment('Hora de inicio del bloque');
            $table->time('end_time')->comment('Hora de fin del bloque');
            $table->string('room', 40)->nullable()->comment('Aula o ambiente');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('Orden dentro del dia');
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['section_id', 'weekday', 'start_time'], 'uq_school_schedule_section_day_start');
            $table->index(['section_id', 'weekday', 'status'], 'ix_school_schedule_section_day');
            $table->index(['teacher_person_id', 'weekday'], 'ix_school_schedule_teacher_day');
            $table->index(['school_id', 'year_id'], 'ix_school_schedule_school_year');
            $table->foreign('area_id')->references('id')->on('aca_school_areas')->nullOnDelete();
            $table->foreign('teacher_person_id')->references('id')->on('people')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('aca_school_schedules')) {
            Schema::table('aca_school_schedules', function (Blueprint $table) {
                $table->dropForeign(['area_id']);
                $table->dropForeign(['teacher_person_id']);
            });
            Schema::dropIfExists('aca_school_schedules');
        }
    }
};
