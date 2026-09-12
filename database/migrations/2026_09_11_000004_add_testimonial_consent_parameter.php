<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el parametro del sistema P000033 con el texto del aviso de
     * autorizacion que el alumno debe aceptar antes de guardar su testimonio.
     *
     * Idempotente: si ya existe, solo actualiza la descripcion.
     */
    public function up(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        $description = 'Texto del aviso de autorizacion para publicar testimonios de alumnos (pagina web y redes sociales)';

        $parameter = Parameter::where('parameter_code', 'P000033')->first();

        if ($parameter) {
            $parameter->update(['description' => $description]);

            return;
        }

        Parameter::create([
            'parameter_code'  => 'P000033',
            'description'     => $description,
            'control_type'    => 'tx',
            'json_query_data' => null,
            'value_default'   => 'Autorizo a CPA Academy a publicar mi testimonio en su pagina web y redes sociales, incluida la imagen que comparti, con fines de difusion academica.',
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        Parameter::where('parameter_code', 'P000033')->delete();
    }
};
