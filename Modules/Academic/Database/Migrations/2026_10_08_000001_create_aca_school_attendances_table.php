<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Registro mensual de asistencia estilo SIAGIE (MINEDU):
        // un registro por matricula y dia con estado A/T/J/F.
        Schema::create('aca_school_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('aca_schools')->cascadeOnDelete();
            $table->foreignId('year_id')->constrained('aca_school_years')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('aca_school_sections')->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained('aca_school_enrollments')->cascadeOnDelete();
            $table->date('attendance_date')->comment('Dia calendario del registro');
            $table->char('status', 1)->comment('A=asistencia, T=tardanza, J=justificada, F=falta');
            $table->unsignedBigInteger('user_id_registers')->nullable();
            $table->timestamps();

            $table->unique(['enrollment_id', 'attendance_date'], 'aca_school_attendances_enrollment_date_unique');
            $table->index(['section_id', 'attendance_date']);
            $table->foreign('user_id_registers')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('aca_school_attendances', function (Blueprint $table) {
            $table->dropForeign(['user_id_registers']);
        });
        Schema::dropIfExists('aca_school_attendances');
    }
};
