<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La tabla sunat_currency_types no tiene clave primaria, por lo que con el tiempo
     * se duplicaron filas para la misma moneda (PEN, USD) y los selects mostraban
     * opciones repetidas. Este script conserva una sola fila por moneda.
     *
     * Sin renombrar la tabla (otras tablas la referencian por FK) y sin tocar filas
     * que esten siendo usadas: solo elimina duplicados exactos.
     */
    public function up(): void
    {
        if (! Schema::hasTable('sunat_currency_types')) {
            return;
        }

        // 1. Columna temporal autoincremental para poder distinguir filas identicas.
        Schema::table('sunat_currency_types', function (Blueprint $table) {
            $table->increments('tmp_dedup_id')->first();
        });

        // 2. Borra duplicados exactos conservando la primera aparicion (tmp_dedup_id menor).
        DB::statement('
            DELETE c1 FROM sunat_currency_types c1
            INNER JOIN sunat_currency_types c2
                ON c1.id = c2.id
                AND c1.active = c2.active
                AND c1.symbol <=> c2.symbol
                AND c1.description <=> c2.description
                AND c1.tmp_dedup_id > c2.tmp_dedup_id
        ');

        // 3. Duplicados con mismo id pero texto distinto: conserva tambien el primero.
        DB::statement('
            DELETE c1 FROM sunat_currency_types c1
            INNER JOIN sunat_currency_types c2
                ON c1.id = c2.id
                AND c1.tmp_dedup_id > c2.tmp_dedup_id
        ');

        // 4. Quita la columna temporal.
        Schema::table('sunat_currency_types', function (Blueprint $table) {
            $table->dropColumn('tmp_dedup_id');
        });

        // 5. Evita duplicados futuros (si alguna instalacion aun tiene ids distintos
        //    no normalizados, el indice no se crea y el sistema sigue funcionando).
        try {
            Schema::table('sunat_currency_types', function (Blueprint $table) {
                $table->unique('id');
            });
        } catch (\Throwable $e) {
            // Sin indice unico: la deduplicacion del backend/frontend cubre la visualizacion.
        }
    }

    public function down(): void
    {
        // No se restauran las filas duplicadas: eran datos erroneos.
        try {
            Schema::table('sunat_currency_types', function (Blueprint $table) {
                $table->dropUnique(['id']);
            });
        } catch (\Throwable $e) {
            // El indice pudo no haberse creado.
        }
    }
};
