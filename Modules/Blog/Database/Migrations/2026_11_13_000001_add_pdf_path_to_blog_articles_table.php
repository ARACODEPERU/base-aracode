<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ruta del PDF opcional adjunto a una entrada del blog.
 *
 * Idempotente a proposito: comprueba la tabla y la columna antes de crearlas,
 * asi que puede ejecutarse varias veces sin duplicar ni fallar.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('blog_articles') || Schema::hasColumn('blog_articles', 'pdf_path')) {
            return;
        }

        Schema::table('blog_articles', function (Blueprint $table) {
            $table->string('pdf_path')->nullable()->after('imagen')
                ->comment('Ruta del PDF adjunto en el disco publico');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('blog_articles') || ! Schema::hasColumn('blog_articles', 'pdf_path')) {
            return;
        }

        Schema::table('blog_articles', function (Blueprint $table) {
            $table->dropColumn('pdf_path');
        });
    }
};
