<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aca_courses', function (Blueprint $table) {
            // 1. Creamos la columna permitiendo null temporalmente
            // para poder guardar la migración sin errores de integridad.
            $table->text('certificate_title')->nullable()->after('description');
        });

        // 2. Copiamos los datos de 'description' a 'certificate_title'
        DB::table('aca_courses')->update([
            'certificate_title' => DB::raw('description')
        ]);

        // 3. Modificamos la columna para que ahora sí sea NOT NULL
        Schema::table('aca_courses', function (Blueprint $table) {
            $table->text('certificate_title')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('aca_courses', function (Blueprint $table) {
            $table->dropColumn('certificate_title');
        });
    }
};
