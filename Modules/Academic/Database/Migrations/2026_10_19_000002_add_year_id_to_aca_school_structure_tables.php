<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Agregar year_id como nullable primero
        if (! Schema::hasColumn('aca_school_levels', 'year_id')) {
            Schema::table('aca_school_levels', function (Blueprint $table) {
                $table->foreignId('year_id')->nullable()->after('school_id')->constrained('aca_school_years')->cascadeOnDelete();
            });
        }
        if (! Schema::hasColumn('aca_school_grades', 'year_id')) {
            Schema::table('aca_school_grades', function (Blueprint $table) {
                $table->foreignId('year_id')->nullable()->after('school_id')->constrained('aca_school_years')->cascadeOnDelete();
            });
        }
        if (! Schema::hasColumn('aca_school_sections', 'year_id')) {
            Schema::table('aca_school_sections', function (Blueprint $table) {
                $table->foreignId('year_id')->nullable()->after('school_id')->constrained('aca_school_years')->cascadeOnDelete();
            });
        }

        // 2) Asignar los registros existentes al año activo de cada colegio.
        //    Si el colegio no tiene año activo, al más reciente. Si no tiene
        //    ningún año, se queda en NULL (queda invisible hasta que se cree un año).
        $this->assignExistingToActiveYear('aca_school_levels');
        $this->assignExistingToActiveYear('aca_school_grades');
        $this->assignExistingToActiveYear('aca_school_sections');

        // 3) Ajustar unicidad de niveles: ahora es por año, no por colegio.
        //    El indice unico (school_id, code) da soporte a la FK sobre school_id,
        //    por lo que hay que eliminar la FK, el indice, crear el nuevo indice
        //    por (year_id, code) y recrear la FK.
        $this->replaceLevelUniqueIndex();
    }

    public function down(): void
    {
        Schema::table('aca_school_levels', function (Blueprint $table) {
            $table->dropUnique(['year_id', 'code']);
            $table->dropForeign(['year_id']);
            $table->dropColumn('year_id');
            $table->unique(['school_id', 'code']);
        });
        Schema::table('aca_school_grades', function (Blueprint $table) {
            $table->dropForeign(['year_id']);
            $table->dropColumn('year_id');
        });
        Schema::table('aca_school_sections', function (Blueprint $table) {
            $table->dropForeign(['year_id']);
            $table->dropColumn('year_id');
        });
    }

    private function assignExistingToActiveYear(string $tableName): void
    {
        // Para cada colegio, resolver el año destino (activo, o el más reciente)
        $schools = DB::table('aca_schools')->pluck('id');

        foreach ($schools as $schoolId) {
            $yearId = DB::table('aca_school_years')
                ->where('school_id', $schoolId)
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                ->orderByDesc('year')
                ->value('id');

            if ($yearId) {
                DB::table($tableName)
                    ->where('school_id', $schoolId)
                    ->whereNull('year_id')
                    ->update(['year_id' => $yearId]);
            }
        }
    }

    /**
     * Reemplaza el unico (school_id, code) por (year_id, code) en niveles,
     * sorteando la dependencia de la FK sobre school_id.
     */
    private function replaceLevelUniqueIndex(): void
    {
        // Schema::getIndexes es portable (SHOW INDEX es exclusivo de MySQL y
        // rompe las pruebas, que corren sobre sqlite en memoria).
        $indexes = collect(Schema::getIndexes('aca_school_levels'))
            ->pluck('name')->unique()->all();

        // Si ya existe el unico por año, no hacer nada.
        if (in_array('aca_school_levels_year_id_code_unique', $indexes, true)) {
            return;
        }

        // Eliminar la FK sobre school_id (usa el indice compuesto como soporte).
        Schema::table('aca_school_levels', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
        });

        // Eliminar el indice unico viejo si existe.
        if (in_array('aca_school_levels_school_id_code_unique', $indexes, true)) {
            Schema::table('aca_school_levels', function (Blueprint $table) {
                $table->dropUnique('aca_school_levels_school_id_code_unique');
            });
        }

        // Crear el indice unico por año y el indice simple de soporte para la FK.
        Schema::table('aca_school_levels', function (Blueprint $table) {
            $table->unique(['year_id', 'code']);
            $table->index('school_id');
            $table->foreign('school_id')->references('id')->on('aca_schools')->cascadeOnDelete();
        });
    }
};
