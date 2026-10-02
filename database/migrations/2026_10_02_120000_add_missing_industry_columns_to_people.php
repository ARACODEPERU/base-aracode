<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reparacion idempotente para bases de datos antiguas.
     *
     * La columna people.industry_id se agrego despues a la migracion
     * 2025_01_13_105819_add_column_people_aditionals (que ya estaba aplicada en
     * instalaciones previas), por lo que esas bases quedaron sin la columna y
     * `php artisan migrate` no la crea (la migracion figura como ejecutada).
     *
     * occupation_id se escribe junto a industry_id en los controladores de
     * negociaciones, asi que se repara con la misma guarda.
     */
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            if (! Schema::hasColumn('people', 'industry_id')) {
                $table->unsignedBigInteger('industry_id')->nullable()->after('industry');
            }

            if (! Schema::hasColumn('people', 'occupation_id')) {
                $table->unsignedSmallInteger('occupation_id')->nullable()->after('profession_id')->comment('id de la ocupacion o cargo');
            }
        });
    }

    public function down(): void
    {
        // No se elimina: son columnas de reparacion que otras instalaciones ya
        // tenian de forma legitima.
    }
};
