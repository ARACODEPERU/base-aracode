<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jornada escolar: hora de entrada y salida del colegio por nivel y turno.
     *
     * Es la base de dos cosas: la grilla del horario (los bloques de clase deben
     * caer dentro de la jornada) y, mas adelante, el registro de asistencia de
     * porteria (una salida antes de la hora oficial es "salida anticipada" y
     * exige motivo). level_id nulo = la jornada aplica a todo el colegio.
     * Idempotente y sin eliminacion de tablas (skill no-drop-tables).
     */
    public function up(): void
    {
        if (Schema::hasTable('aca_school_journeys')) {
            return;
        }

        Schema::create('aca_school_journeys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('aca_schools')->cascadeOnDelete();
            $table->unsignedBigInteger('level_id')->nullable()->comment('Nivel al que aplica; null = todo el colegio');
            $table->string('shift', 20)->default('manana')->comment('manana|tarde|noche|jornada');
            $table->time('entry_time')->comment('Hora oficial de entrada');
            $table->time('exit_time')->comment('Hora oficial de salida');
            $table->unsignedSmallInteger('tolerance_minutes')->default(10)
                ->comment('Minutos de gracia antes de marcar tardanza');
            $table->time('recess_start')->nullable()->comment('Inicio del recreo');
            $table->time('recess_end')->nullable()->comment('Fin del recreo');
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['school_id', 'level_id', 'shift'], 'uq_school_journey_level_shift');
            $table->index(['school_id', 'status']);
            $table->foreign('level_id')->references('id')->on('aca_school_levels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('aca_school_journeys')) {
            Schema::table('aca_school_journeys', function (Blueprint $table) {
                $table->dropForeign(['level_id']);
            });
            Schema::dropIfExists('aca_school_journeys');
        }
    }
};
