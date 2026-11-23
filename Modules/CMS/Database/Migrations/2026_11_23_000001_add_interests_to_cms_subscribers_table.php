<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Temas de interés que el visitante marca en el paso 2 de la landing de
     * cursos gratis. Se guardan como lista de claves del catálogo
     * Modules\CMS\Support\FreeCourseTopics.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('cms_subscribers', 'interests')) {
            Schema::table('cms_subscribers', function (Blueprint $table) {
                $table->json('interests')
                    ->nullable()
                    ->after('subject')
                    ->comment('Temas de interés elegidos en la landing de cursos gratis');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cms_subscribers', 'interests')) {
            Schema::table('cms_subscribers', function (Blueprint $table) {
                $table->dropColumn('interests');
            });
        }
    }
};
