<?php

use App\Models\Parameter;
use App\Support\PaginaPrincipal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de portadas anterior a esta migración: solo las dos del sitio
     * corporativo. Es lo que se restaura al revertir.
     */
    private const CATALOGO_ANTERIOR = '[{"value": "1","label": "Aracode Principal"},{"value": "2","label": "Aracode torneos"}]';

    /**
     * Publica en el parámetro PW00001 las portadas que el sistema ofrece como
     * página principal.
     *
     * El catálogo real vive en config/pagina_principal.php
     * (App\Support\PaginaPrincipal) y ahora incluye las portadas de los sitios
     * de producto (KAPTA LMS, KIRAFACT y Pichanguero), que antes no se podían
     * elegir desde Parámetros.
     *
     * No cambia la portada publicada: quien ya tenía elegida la corporativa o la
     * de torneos la conserva. Solo se corrige el valor si guarda uno que ya no
     * existe en el catálogo, porque en ese caso el sitio muestra la portada por
     * defecto y conviene que el parámetro diga lo que de verdad se publica.
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

        $cambios = ['json_query_data' => PaginaPrincipal::opcionesJson()];

        if (! in_array(trim((string) $parameter->value_default), array_keys(PaginaPrincipal::paginas()), true)) {
            $cambios['value_default'] = PaginaPrincipal::POR_DEFECTO;
        }

        $parameter->update($cambios);
    }

    /**
     * Deja el parámetro con el catálogo anterior (las dos portadas del sitio
     * corporativo). El valor elegido no se toca: revertir no cambia lo publicado.
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
