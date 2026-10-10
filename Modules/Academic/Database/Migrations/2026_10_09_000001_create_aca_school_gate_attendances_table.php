<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Asistencia institucional (portería): el registro de la INSTITUCIÓN,
         * no el del aula. Una fila por alumno y día, con la hora real en que
         * entró y en que salió por la puerta.
         *
         * Es un concepto distinto de aca_school_attendances, que es la planilla
         * mensual SIAGIE del docente (una letra A/T/J/F por día que califica la
         * asistencia a clase). Aquí solo se registra el paso por portería; la
         * letra de aula la sigue escribiendo el docente y, cuando la entrada la
         * genera la portería, se guarda su id en classroom_attendance_id para
         * rastrear el origen de la marca sin duplicar la fuente de verdad.
         */
        Schema::create('aca_school_gate_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('aca_schools')->cascadeOnDelete();
            $table->foreignId('year_id')->constrained('aca_school_years')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('aca_school_sections')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('aca_school_enrollments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('aca_school_students')->cascadeOnDelete();

            $table->date('attendance_date')->comment('Dia calendario de la asistencia institucional');
            $table->dateTime('entry_at')->nullable()->comment('Hora exacta del primer escaneo de entrada');
            $table->char('entry_status', 1)->nullable()->comment('A=asistencia, T=tardanza, segun la jornada del nivel');
            $table->dateTime('exit_at')->nullable()->comment('Hora exacta del ultimo escaneo de salida');
            $table->boolean('early_exit')->default(false)->comment('Salida antes de la hora oficial de salida');
            $table->unsignedSmallInteger('scans_count')->default(0)->comment('Veces que paso por la puerta en el dia');
            $table->string('last_event', 3)->nullable()->comment('in|out: ultimo evento de porteria registrado');
            $table->text('observations')->nullable()->comment('Motivo de la salida anticipada, si se registro');

            // Trazabilidad: la marca de aula que genero la entrada del dia.
            $table->foreignId('classroom_attendance_id')->nullable()
                ->constrained('aca_school_attendances')->nullOnDelete();
            $table->unsignedBigInteger('user_id_registers')->nullable();

            $table->timestamps();

            // Una sola asistencia institucional por alumno y dia: es lo que hace
            // idempotente al segundo escaneo (responde "ya registrado").
            $table->unique(['enrollment_id', 'attendance_date'], 'aca_school_gate_attendances_enrollment_date_unique');
            $table->index(['school_id', 'attendance_date'], 'aca_school_gate_attendances_school_date_index');
            $table->index(['student_id', 'attendance_date'], 'aca_school_gate_attendances_student_date_index');
            $table->foreign('user_id_registers')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('aca_school_gate_attendances', function (Blueprint $table) {
            $table->dropForeign(['user_id_registers']);
        });
        Schema::dropIfExists('aca_school_gate_attendances');
    }
};
