<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agrega las nuevas opciones para los cursos:
     * - Categoría: "Gestión Humana"
     * - Tipo: "Bootcamp"
     * - Sector: "Recursos Humanos"
     *
     * @return void
     */
    public function up()
    {
        // 1. Nueva categoría
        DB::table('aca_category_courses')->insert([
            'description' => 'Gestión Humana',
            'capacitation' => true,
            'image' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Obtener todos los valores existentes en type_description para incluirlos en el nuevo ENUM
        $tiposExistentes = DB::table('aca_courses')
            ->whereNotNull('type_description')
            ->distinct()
            ->pluck('type_description')
            ->toArray();
        $todosTipos = array_unique(array_merge($tiposExistentes, ['Webinar', 'Cursos Taller', 'Programas de Especialización', 'Bootcamp']));
        $tiposEnum = implode(',', array_map(fn($t) => "'" . addslashes($t) . "'", $todosTipos));
        DB::statement("ALTER TABLE aca_courses MODIFY COLUMN type_description ENUM($tiposEnum) NULL");

        // 3. Obtener todos los valores existentes en sector_description para incluirlos en el nuevo ENUM
        $sectoresExistentes = DB::table('aca_courses')
            ->whereNotNull('sector_description')
            ->distinct()
            ->pluck('sector_description')
            ->toArray();
        $todosSectores = array_unique(array_merge($sectoresExistentes, ['Contabilidad', 'Recursos Humanos']));
        $sectoresEnum = implode(',', array_map(fn($s) => "'" . addslashes($s) . "'", $todosSectores));
        DB::statement("ALTER TABLE aca_courses MODIFY COLUMN sector_description ENUM($sectoresEnum) NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 1. Revertir sector
        DB::statement("ALTER TABLE aca_courses MODIFY COLUMN sector_description ENUM('Contabilidad') NULL");

        // 2. Revertir tipo
        DB::statement("ALTER TABLE aca_courses MODIFY COLUMN type_description ENUM('Webinar','Cursos Taller','Programas de Especialización') NULL");

        // 3. Eliminar la categoría solo si ningún curso la está usando
        DB::table('aca_category_courses')
            ->where('description', 'Gestión Humana')
            ->whereNotIn('id', function ($query) {
                $query->select('category_id')
                    ->from('aca_courses')
                    ->whereNotNull('category_id');
            })
            ->delete();
    }
};
