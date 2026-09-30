<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Integrationhub\Entities\Integration;
use Modules\Integrationhub\Entities\IntegrationEndpoint;
use Modules\Integrationhub\Entities\IntegrationFieldMap;

return new class extends Migration
{
    private const INTEGRATION_NAME = 'Telegram_bot';

    private const ENDPOINT_NAME = 'telegram_send_message';

    /**
     * Modo de formato del endpoint de envio.
     *
     * Telegram solo interpreta las etiquetas cuando la peticion manda
     * parse_mode; sin el campo, un texto con <b> llegaria con las etiquetas
     * visibles. Va fijo en HTML porque el texto que sale por este endpoint ya
     * viaja escapado (ver Support\TelegramHtml): los mensajes configurados como
     * "texto plano" se envian con sus simbolos escapados, no crudos.
     *
     * Idempotente: si el campo ya existe no se vuelve a crear ni se pisa el
     * valor que alguien haya ajustado a mano.
     */
    public function up(): void
    {
        if (! Schema::hasTable('integrations')
            || ! Schema::hasTable('integration_endpoints')
            || ! Schema::hasTable('integration_field_maps')) {
            return;
        }

        $endpoint = IntegrationEndpoint::query()
            ->where('name', self::ENDPOINT_NAME)
            ->whereHas('integration', fn ($query) => $query->where('name', self::INTEGRATION_NAME))
            ->first();

        if (! $endpoint) {
            return;
        }

        IntegrationFieldMap::firstOrCreate(
            [
                'endpoint_id' => $endpoint->id,
                'field_key' => 'parse_mode',
                'field_location' => 'body',
            ],
            [
                'field_value' => 'HTML',
                'field_type' => 'static',
                'source_type' => 'static',
                'source_table' => null,
                'source_field' => null,
                'default_value' => null,
                'is_required' => false,
                'is_enabled' => true,
                'sort_order' => 4,
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('integration_field_maps')) {
            return;
        }

        $endpoint = IntegrationEndpoint::query()
            ->where('name', self::ENDPOINT_NAME)
            ->whereHas('integration', fn ($query) => $query->where('name', self::INTEGRATION_NAME))
            ->first();

        if (! $endpoint) {
            return;
        }

        IntegrationFieldMap::where('endpoint_id', $endpoint->id)
            ->where('field_key', 'parse_mode')
            ->delete();
    }
};
