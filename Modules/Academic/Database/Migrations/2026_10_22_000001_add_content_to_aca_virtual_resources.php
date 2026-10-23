<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los recursos de tipo "embed" funcionan como una pagina de texto: el
     * docente escribe el contenido directamente en el aula y no depende de un
     * archivo o de un enlace externo.
     */
    public function up(): void
    {
        if (Schema::hasColumn('aca_virtual_resources', 'content')) {
            return;
        }

        Schema::table('aca_virtual_resources', function (Blueprint $table) {
            $table->longText('content')->nullable()->after('url')->comment('Contenido de la pagina de texto');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('aca_virtual_resources', 'content')) {
            return;
        }

        Schema::table('aca_virtual_resources', function (Blueprint $table) {
            $table->dropColumn('content');
        });
    }
};
