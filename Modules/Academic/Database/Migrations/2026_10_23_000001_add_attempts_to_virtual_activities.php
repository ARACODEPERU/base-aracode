<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('aca_virtual_activities', 'attempts_allowed')) {
            Schema::table('aca_virtual_activities', function (Blueprint $table) {
                // Número de intentos que el docente habilita para una evaluación.
                $table->unsignedTinyInteger('attempts_allowed')->default(1)->after('allow_late');
                $table->boolean('allow_result_download')->default(false)->after('attempts_allowed');
            });
        }

        if (! Schema::hasColumn('aca_virtual_quiz_attempts', 'attempt_number')) {
            Schema::table('aca_virtual_quiz_attempts', function (Blueprint $table) {
                $table->unsignedInteger('attempt_number')->default(1)->after('student_id');
            });
        }

        if (! Schema::hasTable('aca_virtual_quiz_attempts')) {
            return;
        }

        $indexes = collect(Schema::getIndexes('aca_virtual_quiz_attempts'))->pluck('name');

        // Primero se crea el índice nuevo: la clave foránea de activity_id
        // necesita un índice y, en MySQL, no se puede quitar el antiguo antes.
        if (! $indexes->contains('uq_aca_virtual_attempt_activity_student_number')) {
            Schema::table('aca_virtual_quiz_attempts', function (Blueprint $table) {
                $table->unique(
                    ['activity_id', 'student_id', 'attempt_number'],
                    'uq_aca_virtual_attempt_activity_student_number'
                );
            });
        }

        // Antes solo se guardaba un intento por alumno y actividad. Ahora se
        // conserva el historial y la nota vigente es la del último intento.
        if ($indexes->contains('uq_aca_virtual_attempt_activity_student')) {
            Schema::table('aca_virtual_quiz_attempts', function (Blueprint $table) {
                $table->dropUnique('uq_aca_virtual_attempt_activity_student');
            });
        }
    }

    public function down(): void
    {
        $indexes = Schema::hasTable('aca_virtual_quiz_attempts')
            ? collect(Schema::getIndexes('aca_virtual_quiz_attempts'))->pluck('name')
            : collect();

        // Al revertir se vuelve al esquema anterior. Si ya existen varios
        // intentos, el índice antiguo no puede crearse: se conserva el nuevo.
        if ($indexes->contains('uq_aca_virtual_attempt_activity_student_number')
            && Schema::hasTable('aca_virtual_quiz_attempts')
            && ! $indexes->contains('uq_aca_virtual_attempt_activity_student')) {
            $duplicates = \Illuminate\Support\Facades\DB::table('aca_virtual_quiz_attempts')
                ->select('activity_id', 'student_id')
                ->groupBy('activity_id', 'student_id')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            if (! $duplicates) {
                Schema::table('aca_virtual_quiz_attempts', function (Blueprint $table) {
                    $table->unique(['activity_id', 'student_id'], 'uq_aca_virtual_attempt_activity_student');
                });
            }
        }

        if (Schema::hasColumn('aca_virtual_activities', 'attempts_allowed')) {
            Schema::table('aca_virtual_activities', function (Blueprint $table) {
                $table->dropColumn(['attempts_allowed', 'allow_result_download']);
            });
        }

        if (Schema::hasColumn('aca_virtual_quiz_attempts', 'attempt_number')) {
            Schema::table('aca_virtual_quiz_attempts', function (Blueprint $table) {
                $table->dropColumn('attempt_number');
            });
        }
    }
};
