<?php

namespace Tests\Unit\Database;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/*
 * La tabla de monedas se creo sin clave unica en `id`, asi que repetir el insert
 * de la migracion original dejaba filas duplicadas y los selects de moneda
 * mostraban la misma moneda tres veces. Esta prueba fija el contrato de la
 * migracion de limpieza: deja una fila por moneda y blinda la tabla.
 */
class DedupeSunatCurrencyTypesMigrationTest extends TestCase
{
    private const TABLE = 'sunat_currency_types';

    private const MIGRATION = 'database/migrations/2026_09_16_000000_dedupe_and_unique_sunat_currency_types.php';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create(self::TABLE, function (Blueprint $table) {
            $table->string('id')->index();
            $table->boolean('active');
            $table->string('symbol')->nullable();
            $table->string('description');
        });
    }

    public function test_deja_una_sola_fila_por_moneda(): void
    {
        $this->sembrarDuplicados();

        $this->migracion()->up();

        $this->assertSame(1, DB::table(self::TABLE)->where('id', 'PEN')->count());
        $this->assertSame(1, DB::table(self::TABLE)->where('id', 'USD')->count());
        $this->assertSame(2, DB::table(self::TABLE)->count());
    }

    public function test_conserva_la_descripcion_mas_baja_cuando_las_filas_difieren(): void
    {
        DB::table(self::TABLE)->insert([
            ['id' => 'PEN', 'active' => true, 'symbol' => 'S/', 'description' => 'Soles (S/)'],
            ['id' => 'PEN', 'active' => true, 'symbol' => 'S/', 'description' => 'Soles'],
        ]);

        $this->migracion()->up();

        // "Soles" se muestra antes que "Soles (S/)": se conserva la mas simple.
        $this->assertSame('Soles', DB::table(self::TABLE)->where('id', 'PEN')->value('description'));
    }

    public function test_el_indice_unico_impide_volver_a_duplicar(): void
    {
        $this->sembrarDuplicados();

        $this->migracion()->up();

        $this->expectException(QueryException::class);

        DB::table(self::TABLE)->insert([
            'id' => 'PEN', 'active' => true, 'symbol' => 'S/', 'description' => 'Soles',
        ]);
    }

    public function test_la_reversion_quita_el_indice_unico(): void
    {
        $this->sembrarDuplicados();

        $migration = $this->migracion();
        $migration->up();
        $migration->down();

        DB::table(self::TABLE)->insert([
            'id' => 'PEN', 'active' => true, 'symbol' => 'S/', 'description' => 'Soles',
        ]);

        $this->assertSame(2, DB::table(self::TABLE)->where('id', 'PEN')->count());
    }

    private function sembrarDuplicados(): void
    {
        $filas = [
            ['id' => 'PEN', 'active' => true, 'symbol' => 'S/', 'description' => 'Soles'],
            ['id' => 'USD', 'active' => true, 'symbol' => '$', 'description' => 'Dólares Americanos'],
        ];

        foreach ([1, 2, 3] as $vez) {
            DB::table(self::TABLE)->insert($filas);
        }
    }

    private function migracion(): object
    {
        return require base_path(self::MIGRATION);
    }
}
