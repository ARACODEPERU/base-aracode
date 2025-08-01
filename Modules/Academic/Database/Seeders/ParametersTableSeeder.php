<?php

namespace Modules\Academic\Database\Seeders;

use App\Models\Parameter;
use Illuminate\Database\Seeder;

class ParametersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Parameter::create([
            'parameter_code'    => 'P000018',
            'description'       => 'Activar campos para datos adicionales del curso (Descripción del Certificado, Aplicación de Descuento)',
            'control_type'      => 'chx',
            'json_query_data'   => null,
            'value_default'     => 1
        ]);
    }
}
