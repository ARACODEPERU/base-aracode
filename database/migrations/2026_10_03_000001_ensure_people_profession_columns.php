<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reparacion idempotente para bases de datos antiguas.
     *
     * Las columnas people.profession_id y people.profession se crearon en la
     * migracion 2025_05_21_113917 (profession_id) y 2025_01_13_105819
     * (profession). En instalaciones donde esas migraciones ya figuraban como
     * ejecutadas antes de incorporar las columnas, `php artisan migrate` no las
     * vuelve a crear; esta migracion las garantiza sin tocar nada si ya existen.
     */
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            if (! Schema::hasColumn('people', 'profession_id')) {
                $table->unsignedSmallInteger('profession_id')->nullable()->after('industry_id')->comment('id de la profesion');
            }

            if (! Schema::hasColumn('people', 'profession')) {
                $table->string('profession', 100)->nullable()->after('profession_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se elimina: son columnas que otras instalaciones ya tenian de forma
        // legitima, creadas por migraciones anteriores.
    }
};
