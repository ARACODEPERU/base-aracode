<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cargo o profesion del autor del testimonio (por ejemplo "Contador Publico").
     * Si queda vacio, la web muestra el tipo del curso y, en su defecto, "Egresado".
     *
     * Idempotente: la columna se verifica antes de crearse.
     */
    public function up(): void
    {
        if (!Schema::hasTable('cms_testimonies')) {
            return;
        }

        if (!Schema::hasColumn('cms_testimonies', 'author_role')) {
            Schema::table('cms_testimonies', function (Blueprint $table) {
                $table->string('author_role', 255)->nullable()->after('author_name')
                    ->comment('Cargo o profesion del autor del testimonio');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('cms_testimonies')) {
            return;
        }

        if (Schema::hasColumn('cms_testimonies', 'author_role')) {
            Schema::table('cms_testimonies', function (Blueprint $table) {
                $table->dropColumn('author_role');
            });
        }
    }
};
