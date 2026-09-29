<?php

use App\Models\Parameter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PARAMETER_CODE = 'SC-00001';

    private const OLD_DESCRIPTION = 'Credenciales de Vonage (Messages API) para notificaciones por SMS: JSON con application_id, private_key y sender';

    private const NEW_DESCRIPTION = 'Vonage: API Secret para notificaciones por SMS (admite tambien "api_key:api_secret" o un JSON con api_key, api_secret y sender)';

    /**
     * El parametro SC-00001 ya guarda el API Secret de Vonage (autenticacion
     * Basic de la Messages API), no un JSON con clave privada, asi que se
     * actualiza su descripcion para que el administrador sepa que pegar.
     *
     * Migracion aparte porque la migracion original ya corrio en las
     * instalaciones existentes y no se vuelve a ejecutar.
     */
    public function up(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        // Se actualiza por codigo (hay una sola fila SC-00001), sin exigir la
        // descripcion previa porque un administrador pudo editarla.
        Parameter::where('parameter_code', self::PARAMETER_CODE)
            ->update(['description' => self::NEW_DESCRIPTION]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('parameters')) {
            return;
        }

        Parameter::where('parameter_code', self::PARAMETER_CODE)
            ->where('description', self::NEW_DESCRIPTION)
            ->update(['description' => self::OLD_DESCRIPTION]);
    }
};
