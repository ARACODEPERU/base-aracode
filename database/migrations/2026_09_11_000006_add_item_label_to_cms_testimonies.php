<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda el "producto o servicio" como texto libre en los testimonios
     * editoriales, sin obligar a elegir un item de tienda existente.
     *
     * Idempotente: la columna se verifica antes de crearse.
     */
    public function up(): void
    {
        if (!Schema::hasTable('cms_testimonies')) {
            return;
        }

        if (!Schema::hasColumn('cms_testimonies', 'item_label')) {
            Schema::table('cms_testimonies', function (Blueprint $table) {
                $table->string('item_label', 255)->nullable()->after('item_id')
                    ->comment('Producto o servicio escrito libremente por el admin (texto libre)');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('cms_testimonies')) {
            return;
        }

        if (Schema::hasColumn('cms_testimonies', 'item_label')) {
            Schema::table('cms_testimonies', function (Blueprint $table) {
                $table->dropColumn('item_label');
            });
        }
    }
};
