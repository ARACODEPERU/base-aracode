<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Parámetros de control del presupuesto de IA.
 */
return new class extends Migration
{
    private const PARAMETERS = [
        'IA-00013' => [
            'description' => 'Porcentaje del presupuesto mensual de IA para avisar antes de llegar al límite (0 = sin aviso)',
            'control_type' => 'tx',
            'value' => '80',
        ],
        'IA-00014' => [
            'description' => 'Cortar las consultas de IA al superar el presupuesto mensual (1 = sí, 0 = solo avisar)',
            'control_type' => 'chx',
            'value' => '1',
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        foreach (self::PARAMETERS as $code => $attributes) {
            $parameter = Parameter::firstOrNew(['parameter_code' => $code]);

            $parameter->fill([
                'description' => $attributes['description'],
                'control_type' => $attributes['control_type'],
                'json_query_data' => null,
            ]);

            if (! $parameter->exists) {
                $parameter->value_default = $attributes['value'];
            }

            $parameter->save();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        Parameter::whereIn('parameter_code', array_keys(self::PARAMETERS))->delete();
    }
};
