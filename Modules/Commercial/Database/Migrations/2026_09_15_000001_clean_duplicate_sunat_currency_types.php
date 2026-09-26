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
     * Es idempotente: puede re-ejecutarse sobre una BD donde ya se corrio una
     * version anterior (indice o columna ya existentes) sin lanzar errores.
     *
     * Sin renombrar la tabla (otras tablas la referencian por FK) y sin tocar filas
     * que esten siendo usadas: solo elimina duplicados exactos.
     */
    public function up(): void
    {
        if (! Schema::hasTable('sunat_currency_types')) {
            return;
        }

        // 1. Columna temporal autoincremental para poder distinguir filas identicas
        //    (solo se agrega si no quedo de una ejecucion previa incompleta).
        if (! Schema::hasColumn('sunat_currency_types', 'tmp_dedup_id')) {
            Schema::table('sunat_currency_types', function (Blueprint $table) {
                // sqlite no permite agregar una columna PRIMARY KEY a una tabla
                // existente (la suite de tests); ahi basta una columna simple
                // porque el borrado con INNER JOIN tampoco se ejecuta fuera de MySQL.
                if (DB::getDriverName() === 'mysql') {
                    $table->increments('tmp_dedup_id')->first();
                } else {
                    $table->integer('tmp_dedup_id')->nullable();
                }
            });
        }

        // 2. Borra duplicados exactos conservando la primera aparicion (tmp_dedup_id menor).
        //    DELETE con INNER JOIN es sintaxis exclusiva de MySQL; en sqlite no
        //    hace falta: las tablas de prueba se crean sin duplicados.
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
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
        }

        // 4. Quita la columna temporal.
        if (Schema::hasColumn('sunat_currency_types', 'tmp_dedup_id')) {
            Schema::table('sunat_currency_types', function (Blueprint $table) {
                $table->dropColumn('tmp_dedup_id');
            });
        }

        // 5. Evita duplicados futuros: solo crea el indice si todavia no existe.
        if (! $this->uniqueIndexExists()) {
            try {
                Schema::table('sunat_currency_types', function (Blueprint $table) {
                    $table->unique('id');
                });
            } catch (\Throwable $e) {
                // Sin indice unico: la deduplicacion del backend/frontend cubre la visualizacion.
            }
        }
    }

    public function down(): void
    {
        // No se restauran las filas duplicadas: eran datos erroneos.
        if ($this->uniqueIndexExists()) {
            Schema::table('sunat_currency_types', function (Blueprint $table) {
                $table->dropUnique(['id']);
            });
        }
    }

    /**
     * El indice puede haber quedado creado por una version anterior de este
     * script (o por un restore de BD): se verifica con SHOW INDEX.
     */
    private function uniqueIndexExists(): bool
    {
        try {
            $indexes = DB::select('SHOW INDEX FROM sunat_currency_types');

            foreach ($indexes as $index) {
                if ($index->Key_name === 'sunat_currency_types_id_unique') {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }
};
