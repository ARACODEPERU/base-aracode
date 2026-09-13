<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el parametro PHD0001: cuota de almacenamiento del sistema en GB.
     * Es editable desde la pantalla de Parametros y es la UNICA fuente de la
     * capacidad contra la cual se compara el espacio usado en el indicador
     * del dashboard (no se usa el disco real del servidor).
     *
     * IMPORTANTE: esta migracion NO modifica parametros existentes. Si la
     * instalacion ya tiene la cuota registrada con el codigo anterior
     * (P000032), se deja intacta: el servicio la lee como respaldo de solo
     * lectura. PHD0001 solo se crea en instalaciones donde no existe
     * ninguna de las dos.
     *
     * Valor inicial (solo instalacion nueva): DISC_SPACE del .env si es
     * numerico; de lo contrario 10 GB.
     */
    public function up(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        // Nada que crear si ya existe cualquiera de las dos variantes.
        // No se renombra, no se actualiza, no se toca nada existente.
        $existsNew = Parameter::where('parameter_code', 'PHD0001')->exists();
        $existsLegacy = Parameter::where('parameter_code', 'P000032')->exists();

        if ($existsNew || $existsLegacy) {
            return;
        }

        $initialQuota = trim((string) env('DISC_SPACE', ''));

        if ($initialQuota === '' || !is_numeric($initialQuota) || (float) $initialQuota <= 0) {
            $initialQuota = '10';
        }

        Parameter::create([
            'parameter_code'   => 'PHD0001',
            'description'      => 'Cuota de almacenamiento del sistema en GB (indicador del dashboard)',
            'control_type'     => 'tx',
            'json_query_data'  => null,
            'value_default'    => $initialQuota,
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('parameters')) {
            return;
        }

        // Elimina unicamente la fila que esta migracion pudo haber creado
        // (identificada por su descripcion, para no borrar datos ajenos).
        Parameter::where('parameter_code', 'PHD0001')
            ->where('description', 'Cuota de almacenamiento del sistema en GB (indicador del dashboard)')
            ->delete();
    }
};
