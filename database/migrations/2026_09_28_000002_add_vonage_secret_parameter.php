<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Parametro que guarda las credenciales de Vonage (Messages API). */
    private const PARAMETER_CODE = 'SC-00001';

    /** Descripcion visible en Parametros del sistema. */
    private const DESCRIPTION = 'Vonage: API Secret para notificaciones por SMS (admite tambien "api_key:api_secret" o un JSON con api_key, api_secret y sender)';

    /**
     * Crea el parametro que alimenta el envio de SMS.
     *
     * El valor es el API Secret del panel de Vonage (autenticacion Basic de la
     * Messages API); la clave publica va en config/env VONAGE_API_KEY. Tambien
     * se acepta "api_key:api_secret" o un JSON con api_key, api_secret y
     * sender. Si el parametro esta vacio, la opcion "SMS via Vonage" no se
     * muestra en la interfaz.
     *
     * Idempotente: si ya existe, solo se actualiza su descripcion.
     */
    public function up(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        $parameter = Parameter::where('parameter_code', self::PARAMETER_CODE)->first();

        if ($parameter) {
            $parameter->update(['description' => self::DESCRIPTION]);

            return;
        }

        Parameter::create([
            'parameter_code'  => self::PARAMETER_CODE,
            'description'     => self::DESCRIPTION,
            'control_type'    => 'tx',
            'json_query_data' => null,
            'value_default'   => null,
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        // Revertir solo la fila propia (por descripcion, para no borrar datos ajenos).
        Parameter::where('parameter_code', self::PARAMETER_CODE)
            ->where('description', self::DESCRIPTION)
            ->delete();
    }
};
