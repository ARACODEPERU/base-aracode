<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convierte cms_testimonies en la tabla unica de testimonios:
     *  - editoriales (source = 'cms')
     *  - de alumnos reales (source = 'student', student_id)
     *  - creados por el admin "como si fuesen de alumnos" (source = 'student', student_id = null)
     *
     * Idempotente: cada columna se verifica antes de crearse.
     */
    public function up(): void
    {
        if (!Schema::hasTable('cms_testimonies')) {
            return;
        }

        Schema::table('cms_testimonies', function (Blueprint $table) {
            if (!Schema::hasColumn('cms_testimonies', 'student_id')) {
                $table->unsignedBigInteger('student_id')->nullable()->after('entitie')->index()
                    ->comment('aca_students.id. null = editorial, seed o testimonio creado por el admin');
            }

            if (!Schema::hasColumn('cms_testimonies', 'course_id')) {
                $table->unsignedBigInteger('course_id')->nullable()->after('student_id')->index()
                    ->comment('aca_courses.id del curso al que se refiere el testimonio');
            }

            if (!Schema::hasColumn('cms_testimonies', 'rating')) {
                $table->unsignedTinyInteger('rating')->nullable()->after('description')
                    ->comment('Calificacion en estrellas (1 a 5)');
            }

            if (!Schema::hasColumn('cms_testimonies', 'source')) {
                $table->string('source', 20)->default('cms')->after('rating')->index()
                    ->comment('cms = editorial | student = alumno');
            }

            if (!Schema::hasColumn('cms_testimonies', 'approval_status')) {
                $table->string('approval_status', 20)->default('approved')->after('source')->index()
                    ->comment('pending | approved | rejected');
            }

            if (!Schema::hasColumn('cms_testimonies', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approval_status');
            }

            if (!Schema::hasColumn('cms_testimonies', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at')
                    ->comment('users.id del admin que aprobo/rechazo');
            }

            if (!Schema::hasColumn('cms_testimonies', 'author_name')) {
                $table->string('author_name')->nullable()->after('approved_by')
                    ->comment('Nombre mostrado del autor (snapshot o nombre libre del admin)');
            }

            if (!Schema::hasColumn('cms_testimonies', 'consent_accepted')) {
                $table->boolean('consent_accepted')->nullable()->after('author_name')
                    ->comment('true = el alumno autorizo publicar su testimonio e imagen. null = no aplica');
            }

            if (!Schema::hasColumn('cms_testimonies', 'consent_accepted_at')) {
                $table->timestamp('consent_accepted_at')->nullable()->after('consent_accepted');
            }

            if (!Schema::hasColumn('cms_testimonies', 'consent_version')) {
                $table->string('consent_version', 60)->nullable()->after('consent_accepted_at')
                    ->comment('Version o hash del aviso de autorizacion aceptado');
            }
        });

        // Los testimonios creados por el admin se enlazan solo al curso, sin item de tienda.
        if (Schema::hasColumn('cms_testimonies', 'item_id')) {
            Schema::table('cms_testimonies', function (Blueprint $table) {
                $table->unsignedBigInteger('item_id')->nullable()->change();
            });
        }

        if (Schema::hasColumn('cms_testimonies', 'entitie')) {
            Schema::table('cms_testimonies', function (Blueprint $table) {
                $table->string('entitie')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('cms_testimonies')) {
            return;
        }

        Schema::table('cms_testimonies', function (Blueprint $table) {
            foreach ([
                'consent_version',
                'consent_accepted_at',
                'consent_accepted',
                'author_name',
                'approved_by',
                'approved_at',
                'approval_status',
                'source',
                'rating',
                'course_id',
                'student_id',
            ] as $column) {
                if (Schema::hasColumn('cms_testimonies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        if (Schema::hasColumn('cms_testimonies', 'item_id')) {
            Schema::table('cms_testimonies', function (Blueprint $table) {
                $table->unsignedBigInteger('item_id')->nullable(false)->change();
            });
        }

        if (Schema::hasColumn('cms_testimonies', 'entitie')) {
            Schema::table('cms_testimonies', function (Blueprint $table) {
                $table->string('entitie')->nullable(false)->change();
            });
        }
    }
};
