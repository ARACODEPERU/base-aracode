<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La primera versión de la migración anterior podía dejar el índice único
 * antiguo (un intento por alumno) porque MySQL no lo eliminaba mientras no
 * existiera otro índice que cubriera la clave foránea. Esta migración deja la
 * base lista para conservar el historial de intentos.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('aca_virtual_quiz_attempts')) {
            return;
        }

        $indexes = collect(Schema::getIndexes('aca_virtual_quiz_attempts'))->pluck('name');

        if (! $indexes->contains('uq_aca_virtual_attempt_activity_student_number')) {
            Schema::table('aca_virtual_quiz_attempts', function (Blueprint $table) {
                $table->unique(
                    ['activity_id', 'student_id', 'attempt_number'],
                    'uq_aca_virtual_attempt_activity_student_number'
                );
            });
        }

        if ($indexes->contains('uq_aca_virtual_attempt_activity_student')) {
            Schema::table('aca_virtual_quiz_attempts', function (Blueprint $table) {
                $table->dropUnique('uq_aca_virtual_attempt_activity_student');
            });
        }
    }

    public function down(): void
    {
        // No se restaura el límite de un solo intento: podría haber historial
        // que se perdería o impediría volver a calificar.
    }
};
