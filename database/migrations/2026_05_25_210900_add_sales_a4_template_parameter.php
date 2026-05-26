<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Parameter::updateOrCreate(
            ['parameter_code' => 'P000026'],
            [
                'description' => 'Plantilla A4 para impresión de documentos de ventas',
                'control_type' => 'rdj',
                'json_query_data' => '[{"value":"1","label":"Formato actual"},{"value":"2","label":"Formato moderno"}]',
                'value_default' => '1',
            ]
        );
    }

    public function down(): void
    {
        Parameter::where('parameter_code', 'P000026')->delete();
    }
};
