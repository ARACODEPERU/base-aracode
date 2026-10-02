<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('aca_school_student_guardians')) {
            Schema::create('aca_school_student_guardians', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('aca_schools')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('aca_school_students')->cascadeOnDelete();
                $table->unsignedBigInteger('guardian_person_id')->nullable()->comment('Persona apoderada (people)');
                $table->string('relationship', 40)->default('otro')->comment('madre|padre|abuelo|abuela|tio|tia|hermano|hermana|primo|prima|padrastro|madrasta|tutor_legal|otro');
                $table->boolean('is_primary')->default(false)->comment('Apoderado principal del alumno');
                $table->boolean('status')->default(true);
                $table->timestamps();

                $table->unique(['student_id', 'guardian_person_id'], 'aca_school_student_guardians_unique');
                $table->index('school_id');
                $table->foreign('guardian_person_id')->references('id')->on('people')->nullOnDelete();
            });

            return;
        }

        // La tabla ya existe por un intento anterior interrumpido (no se elimina,
        // skill no-drop-tables): se completa el estado faltante de forma idempotente.

        if (! Schema::hasColumn('aca_school_student_guardians', 'guardian_person_id')) {
            Schema::table('aca_school_student_guardians', function (Blueprint $table) {
                $table->unsignedBigInteger('guardian_person_id')->nullable()->comment('Persona apoderada (people)')->after('student_id');
            });
        }

        // Hacer nullable la columna si quedó NOT NULL en el intento previo.
        $nullable = DB::selectOne(
            "SELECT IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'aca_school_student_guardians'
               AND COLUMN_NAME = 'guardian_person_id'"
        );

        if ($nullable && $nullable->IS_NULLABLE === 'NO') {
            DB::statement("ALTER TABLE `aca_school_student_guardians` MODIFY `guardian_person_id` BIGINT UNSIGNED NULL COMMENT 'Persona apoderada (people)'");
        }

        // Completar columnas restantes si faltaran en un estado parcial.
        if (! Schema::hasColumn('aca_school_student_guardians', 'relationship')) {
            Schema::table('aca_school_student_guardians', function (Blueprint $table) {
                $table->string('relationship', 40)->default('otro')->comment('madre|padre|abuelo|abuela|tio|tia|hermano|hermana|primo|prima|padrastro|madrasta|tutor_legal|otro')->after('guardian_person_id');
            });
        }

        if (! Schema::hasColumn('aca_school_student_guardians', 'is_primary')) {
            Schema::table('aca_school_student_guardians', function (Blueprint $table) {
                $table->boolean('is_primary')->default(false)->comment('Apoderado principal del alumno')->after('relationship');
            });
        }

        if (! Schema::hasColumn('aca_school_student_guardians', 'status')) {
            Schema::table('aca_school_student_guardians', function (Blueprint $table) {
                $table->boolean('status')->default(true)->after('is_primary');
            });
        }

        if (! Schema::hasColumn('aca_school_student_guardians', 'created_at')) {
            Schema::table('aca_school_student_guardians', function (Blueprint $table) {
                $table->timestamps();
            });
        }

        // Índice único (student_id, guardian_person_id) si no existe.
        $uniqueExists = (bool) DB::selectOne(
            "SELECT COUNT(*) AS total FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'aca_school_student_guardians'
               AND INDEX_NAME = 'aca_school_student_guardians_unique'"
        )->total;

        if (! $uniqueExists) {
            Schema::table('aca_school_student_guardians', function (Blueprint $table) {
                $table->unique(['student_id', 'guardian_person_id'], 'aca_school_student_guardians_unique');
            });
        }

        // FK a people si no existe.
        $fkExists = (bool) DB::selectOne(
            "SELECT COUNT(*) AS total FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'aca_school_student_guardians'
               AND COLUMN_NAME = 'guardian_person_id'
               AND REFERENCED_TABLE_NAME = 'people'"
        )->total;

        if (! $fkExists) {
            Schema::table('aca_school_student_guardians', function (Blueprint $table) {
                $table->foreign('guardian_person_id')->references('id')->on('people')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // No se elimina la tabla (skill no-drop-tables); dejarla intacta.
    }
};
