<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('cms_landings', 'free_courses_count')) {
            Schema::table('cms_landings', function (Blueprint $table) {
                // Cantidad de cursos gratis que muestra el landing (1 a 3).
                $table->unsignedTinyInteger('free_courses_count')
                    ->default(1)
                    ->after('slug')
                    ->comment('cantidad de cursos gratis que muestra el landing (1 a 3)');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cms_landings', 'free_courses_count')) {
            Schema::table('cms_landings', function (Blueprint $table) {
                $table->dropColumn('free_courses_count');
            });
        }
    }
};
