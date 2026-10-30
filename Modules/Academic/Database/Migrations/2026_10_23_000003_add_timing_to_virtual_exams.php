<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('aca_virtual_activities')
            && ! Schema::hasColumn('aca_virtual_activities', 'duration_minutes')) {
            Schema::table('aca_virtual_activities', function (Blueprint $table) {
                // Tiempo que tiene el alumno para resolver cada intento.
                $table->unsignedSmallInteger('duration_minutes')
                    ->default(30)
                    ->after('allow_result_download');
            });
        }

        if (Schema::hasTable('aca_virtual_quiz_attempts')) {
            Schema::table('aca_virtual_quiz_attempts', function (Blueprint $table) {
                if (! Schema::hasColumn('aca_virtual_quiz_attempts', 'expires_at')) {
                    // Cierre real del intento: inicio + duración, nunca después
                    // del cierre de disponibilidad del examen.
                    $table->dateTime('expires_at')->nullable()->after('started_at');
                }

                if (! Schema::hasColumn('aca_virtual_quiz_attempts', 'question_order')) {
                    // Orden aleatorio fijado al iniciar el intento para que no
                    // cambie al recargar la página.
                    $table->json('question_order')->nullable()->after('expires_at');
                }

                if (! Schema::hasColumn('aca_virtual_quiz_attempts', 'option_order')) {
                    // Un arreglo de alternativas por pregunta, también fijado
                    // al iniciar el intento.
                    $table->json('option_order')->nullable()->after('question_order');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('aca_virtual_quiz_attempts')) {
            $columns = array_values(array_filter(
                ['expires_at', 'question_order', 'option_order'],
                fn (string $column) => Schema::hasColumn('aca_virtual_quiz_attempts', $column)
            ));

            if ($columns !== []) {
                Schema::table('aca_virtual_quiz_attempts', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }

        if (Schema::hasTable('aca_virtual_activities')
            && Schema::hasColumn('aca_virtual_activities', 'duration_minutes')) {
            Schema::table('aca_virtual_activities', function (Blueprint $table) {
                $table->dropColumn('duration_minutes');
            });
        }
    }
};
