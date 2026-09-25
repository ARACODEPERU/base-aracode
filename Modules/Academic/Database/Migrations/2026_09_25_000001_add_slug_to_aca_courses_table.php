<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const TABLE = 'aca_courses';

    /**
     * Agrega aca_courses.slug (URL amigable) con indice unico y backfill.
     *
     * Idempotente: en entornos donde la columna o el indice ya existen (agregados
     * a mano o por una version previa de esta migracion) no vuelve a crearlos, y
     * el backfill solo completa filas sin slug o con slug repetido, de modo que
     * el indice unico siempre pueda crearse.
     */
    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        if (! Schema::hasColumn(self::TABLE, 'slug')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->string('slug', 300)->nullable()->after('description');
            });
        }

        $this->backfillSlugs();

        if (! $this->hasSlugUniqueIndex()) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->unique('slug');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        if ($this->hasSlugUniqueIndex()) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropUnique(['slug']);
            });
        }

        if (Schema::hasColumn(self::TABLE, 'slug')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }

    /**
     * Completa el slug de las filas que no lo tengan y renombra los repetidos
     * (el primero conserva su valor). Se ejecuta antes de crear el indice unico.
     *
     * La comparacion es case-insensitive porque la colacion por defecto de MySQL
     * tambien lo es, de modo que el indice unico nunca falle por mayusculas.
     */
    private function backfillSlugs(): void
    {
        $courses = DB::table(self::TABLE)
            ->select('id', 'description', 'slug')
            ->orderBy('id')
            ->get();

        $used = [];
        $repeated = [];

        // Primera pasada: reservar los slug existentes y detectar repetidos.
        foreach ($courses as $course) {
            $current = trim((string) $course->slug);

            if ($current === '') {
                continue;
            }

            $key = Str::lower($current);

            if (isset($used[$key])) {
                $repeated[$course->id] = true;
                continue;
            }

            $used[$key] = true;
        }

        // Segunda pasada: completar vacios y renombrar los repetidos.
        foreach ($courses as $course) {
            $current = trim((string) $course->slug);

            if ($current !== '' && ! isset($repeated[$course->id])) {
                continue;
            }

            $base = $current !== '' ? $current : Str::slug((string) ($course->description ?? ''));
            $base = $base !== '' ? $base : 'curso-' . $course->id;

            $slug = $this->uniqueSlug($base, $used);
            $used[Str::lower($slug)] = true;

            if ($slug !== (string) $course->slug) {
                DB::table(self::TABLE)->where('id', $course->id)->update(['slug' => $slug]);
            }
        }
    }

    /**
     * Devuelve una variante del slug que no colisione con las ya reservadas.
     */
    private function uniqueSlug(string $base, array $used): string
    {
        // Reserva espacio para el sufijo -N dentro del varchar(300).
        $base = Str::limit($base, 280, '') ?: 'curso';

        $slug = $base;
        $i = 2;

        while (isset($used[Str::lower($slug)])) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }

    /**
     * Indica si aca_courses ya tiene un indice unico sobre slug.
     */
    private function hasSlugUniqueIndex(): bool
    {
        if (Schema::hasIndex(self::TABLE, 'aca_courses_slug_unique')) {
            return true;
        }

        foreach (Schema::getIndexes(self::TABLE) as $index) {
            $columns = array_map('strtolower', $index['columns'] ?? []);

            if (($index['unique'] ?? false) && $columns === ['slug']) {
                return true;
            }
        }

        return false;
    }
};
