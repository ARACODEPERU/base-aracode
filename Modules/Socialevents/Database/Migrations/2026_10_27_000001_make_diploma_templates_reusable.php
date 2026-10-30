<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convierte los diseños de diploma en modelos reutilizables por torneo.
 *
 * No se crea una tabla nueva ni se borran datos: la tabla existente conserva
 * los diseños ya guardados y gana tres columnas.
 *
 *  - event_id: torneo dueño del modelo (permite reutilizarlo en otras ediciones).
 *  - diploma_type: team | player, para saber qué variables admite.
 *  - year: año de referencia del modelo, independiente del año de la edición.
 *
 * En `socialevent_edition_awards` se agrega `template_id`: la selección
 * explícita del modelo que se usará al emitir ese premio. Si queda vacío, el
 * sistema usa el modelo vigente del mismo tipo como respaldo.
 *
 * Todo el archivo es idempotente: puede correrse sobre una base nueva o sobre
 * producción ya migrada sin duplicar columnas ni índices.
 */
return new class extends Migration
{
    public function up(): void
    {
        $templates = 'socialevent_edition_diploma_templates';

        if (Schema::hasTable($templates)) {
            Schema::table($templates, function (Blueprint $table) use ($templates) {
                if (! Schema::hasColumn($templates, 'event_id')) {
                    // Sin llave foránea: las filas históricas se rellenan después
                    // y una edición eliminada no debe borrar el modelo.
                    $table->unsignedBigInteger('event_id')->nullable();
                }

                if (! Schema::hasColumn($templates, 'diploma_type')) {
                    $table->string('diploma_type', 10)->default('player');
                }

                if (! Schema::hasColumn($templates, 'year')) {
                    $table->unsignedSmallInteger('year')->nullable();
                }
            });

            $this->addIndexIfMissing($templates, 'se_diploma_tpl_event_type_active_idx', function (Blueprint $table) {
                $table->index(['event_id', 'diploma_type', 'is_active'], 'se_diploma_tpl_event_type_active_idx');
            });

            $this->backfillTemplates($templates);
        }

        if (Schema::hasTable('socialevent_edition_awards') && ! Schema::hasColumn('socialevent_edition_awards', 'template_id')) {
            Schema::table('socialevent_edition_awards', function (Blueprint $table) {
                // Modelo elegido para este premio. Sin FK para que también
                // funcione en SQLite y para no arrastrar premios al borrar un
                // modelo: el servicio valida la compatibilidad.
                $table->unsignedBigInteger('template_id')->nullable();
            });

            $this->addIndexIfMissing('socialevent_edition_awards', 'se_edition_award_template_idx', function (Blueprint $table) {
                $table->index('template_id', 'se_edition_award_template_idx');
            });
        }
    }

    public function down(): void
    {
        $templates = 'socialevent_edition_diploma_templates';

        if (Schema::hasTable('socialevent_edition_awards') && Schema::hasColumn('socialevent_edition_awards', 'template_id')) {
            $this->dropIndexIfExists('socialevent_edition_awards', 'se_edition_award_template_idx');

            Schema::table('socialevent_edition_awards', function (Blueprint $table) {
                $table->dropColumn('template_id');
            });
        }

        if (Schema::hasTable($templates)) {
            $this->dropIndexIfExists($templates, 'se_diploma_tpl_event_type_active_idx');

            $columns = array_values(array_filter(
                ['event_id', 'diploma_type', 'year'],
                fn (string $column) => Schema::hasColumn($templates, $column)
            ));

            if ($columns) {
                Schema::table($templates, function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }

    /**
     * Completa event_id, diploma_type y year en los diseños que ya existían.
     *
     * Se hace fila por fila porque `UPDATE ... JOIN` no funciona en SQLite y
     * las pruebas corren sobre esa base.
     */
    private function backfillTemplates(string $table): void
    {
        $teamKeys = ['champion', 'runner_up', 'third_place', 'fair_play', 'revelation_team'];

        DB::table($table)
            ->whereNull('event_id')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($teamKeys) {
                foreach ($rows as $row) {
                    if (! $row->edition_id) {
                        continue;
                    }

                    $edition = DB::table('event_editions')->where('id', $row->edition_id)->first();

                    if (! $edition) {
                        continue;
                    }

                    $awardKey = (string) ($row->award_key ?? '');

                    DB::table('socialevent_edition_diploma_templates')
                        ->where('id', $row->id)
                        ->update([
                            'event_id' => $edition->event_id,
                            'diploma_type' => in_array($awardKey, $teamKeys, true) ? 'team' : 'player',
                            'year' => $row->year ?: ($edition->year ?: null),
                        ]);
                }
            });
    }

    private function addIndexIfMissing(string $table, string $name, callable $callback): void
    {
        $exists = collect(Schema::getIndexes($table))->contains(fn (array $index) => ($index['name'] ?? '') === $name);

        if (! $exists) {
            Schema::table($table, $callback);
        }
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        $exists = collect(Schema::getIndexes($table))->contains(fn (array $index) => ($index['name'] ?? '') === $name);

        if ($exists) {
            Schema::table($table, fn (Blueprint $table) => $table->dropIndex($name));
        }
    }
};
