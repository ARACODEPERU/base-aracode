<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La tabla de monedas se creo sin clave unica en `id` (solo un indice normal) y
 * con un insert directo, asi que repetir ese insert dejaba filas duplicadas: los
 * selects de moneda de Negociaciones, Contratos y demas pantallas mostraban la
 * misma moneda tres veces. Aqui se deja una sola fila por moneda y se agrega el
 * indice unico para que no pueda volver a pasar.
 */
return new class extends Migration
{
    private const TABLE = 'sunat_currency_types';

    private const UNIQUE_INDEX = 'sunat_currency_types_id_unique';

    public function up(): void
    {
        $duplicadas = DB::table(self::TABLE)
            ->select('id')
            ->groupBy('id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('id');

        foreach ($duplicadas as $id) {
            $sobrantes = DB::table(self::TABLE)->where('id', $id)->count() - 1;

            if ($sobrantes < 1) {
                continue;
            }

            if (DB::getDriverName() === 'mysql') {
                // Se conserva la fila de descripcion mas baja (por ejemplo "Soles"
                // antes que "Soles (S/)"). DELETE ... LIMIT es propio de MySQL.
                $sobrantes = (int) $sobrantes;

                DB::statement(
                    'DELETE FROM ' . self::TABLE . ' WHERE id = ? ORDER BY description DESC LIMIT ' . $sobrantes,
                    [$id]
                );

                continue;
            }

            // En sqlite (pruebas) se borra por rowid, que si admite LIMIT.
            $sobrantes = (int) $sobrantes;

            DB::statement(
                'DELETE FROM ' . self::TABLE . ' WHERE rowid IN ('
                . 'SELECT rowid FROM ' . self::TABLE . ' WHERE id = ? ORDER BY description DESC LIMIT ' . $sobrantes
                . ')',
                [$id]
            );
        }

        // Solo crea el indice si todavia no existe: la deduplicacion de
        // Modules/Commercial (2026_09_15) ya pudo haberlo creado, y re-crearlo
        // lanzaria "Duplicate key name".
        if (! $this->uniqueIndexExists()) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->unique('id', self::UNIQUE_INDEX);
            });
        }
    }

    public function down(): void
    {
        if ($this->uniqueIndexExists()) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropUnique(self::UNIQUE_INDEX);
            });
        }
    }

    private function uniqueIndexExists(): bool
    {
        try {
            foreach (DB::select('SHOW INDEX FROM ' . self::TABLE) as $index) {
                if ($index->Key_name === self::UNIQUE_INDEX) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }
};
