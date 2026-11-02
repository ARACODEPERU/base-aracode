<?php

use App\Models\Parameter;
use App\Support\PaginaPrincipal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo anterior a esta migración: las portadas 1 a 5. Es lo que se
     * restaura al revertir.
     */
    private const CATALOGO_ANTERIOR = '[{"value":"1","label":"Aracode Principal"},{"value":"2","label":"Aracode torneos"},{"value":"3","label":"KAPTA LMS"},{"value":"4","label":"KIRAFACT"},{"value":"5","label":"Pichanguero"}]';

    /**
     * Publica la portada «SALUD» en el parámetro PW00001
     * (Parámetros → «Pagina web principal»).
     *
     * El catálogo real vive en config/pagina_principal.php y ahora incluye la
     * portada del módulo Salud (valor 6). No cambia la portada publicada: quien
     * tenía elegida otra la conserva y SALUD aparece como una opción más para
     * seleccionarla desde Parámetros.
     *
     * Idempotente: se puede volver a ejecutar sin cambiar nada.
     */
    public function up(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        $parameter = Parameter::where('parameter_code', PaginaPrincipal::PARAMETRO)->first();

        if (! $parameter) {
            Parameter::create([
                'parameter_code' => PaginaPrincipal::PARAMETRO,
                'description' => 'Pagina web principal',
                'control_type' => 'sa',
                'json_query_data' => PaginaPrincipal::opcionesJson(),
                'value_default' => PaginaPrincipal::POR_DEFECTO,
            ]);

            return;
        }

        $parameter->update(['json_query_data' => PaginaPrincipal::opcionesJson()]);
    }

    /**
     * Deja el parámetro con el catálogo anterior (las portadas 1 a 5). El valor
     * elegido no se toca: revertir no cambia lo publicado.
     */
    public function down(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        Parameter::where('parameter_code', PaginaPrincipal::PARAMETRO)
            ->update(['json_query_data' => self::CATALOGO_ANTERIOR]);
    }
};
