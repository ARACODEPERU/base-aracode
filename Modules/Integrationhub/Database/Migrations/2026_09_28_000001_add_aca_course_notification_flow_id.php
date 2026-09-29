<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Integrationhub\Entities\IntegrationFlowId;

return new class extends Migration
{
    /**
     * Registra la clave del flujo de WhatsApp que usan las notificaciones de
     * cursos del modulo Academico, para que aparezca en la pestana
     * "Plantillas / Flujos" y el administrador pegue ahi su ID.
     *
     * El flow_id se crea vacio a proposito: mientras este vacio, la opcion de
     * WhatsApp no se muestra en la pantalla de notificaciones.
     */
    public function up(): void
    {
        if (! Schema::hasTable('integration_flow_ids')) {
            return;
        }

        IntegrationFlowId::firstOrCreate(
            ['key' => 'aca_course_notification'],
            [
                'flow_id' => '',
                'label' => 'Notificación de curso (Académico)',
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('integration_flow_ids')) {
            return;
        }

        IntegrationFlowId::where('key', 'aca_course_notification')->delete();
    }
};
