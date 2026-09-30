<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Modules\Integrationhub\Entities\Integration;
use Modules\Integrationhub\Entities\IntegrationEndpoint;
use Modules\Integrationhub\Entities\IntegrationFieldMap;

return new class extends Migration
{
    /** Nombre de la integracion (es la clave natural para reejecutar). */
    private const INTEGRATION_NAME = 'Telegram_bot';

    /**
     * API de Telegram: el token del bot viaja dentro de la ruta, por eso la URL
     * base es la raiz del servicio y cada endpoint incluye "bot{token}".
     */
    private const URL_BASE = 'https://api.telegram.org';

    /**
     * Endpoints de salida contra la API de Telegram.
     *
     * El token siempre es un campo de "Ruta URL" ({token}); el servicio del
     * modulo Academico lo rellena desde el parametro del sistema SC-00002.
     *
     * @var array<int, array{name: string, path: string, method: string, body: string, sort: int}>
     */
    private const ENDPOINTS = [
        [
            'name' => 'telegram_get_me',
            'path' => 'bot{token}/getMe',
            'method' => 'GET',
            'body' => 'none',
            'sort' => 1,
        ],
        [
            'name' => 'telegram_send_message',
            'path' => 'bot{token}/sendMessage',
            'method' => 'POST',
            'body' => 'json',
            'sort' => 2,
        ],
        [
            'name' => 'telegram_set_webhook',
            'path' => 'bot{token}/setWebhook',
            'method' => 'POST',
            'body' => 'json',
            'sort' => 3,
        ],
        [
            'name' => 'telegram_delete_webhook',
            'path' => 'bot{token}/deleteWebhook',
            'method' => 'POST',
            'body' => 'json',
            'sort' => 4,
        ],
        [
            'name' => 'telegram_get_updates',
            'path' => 'bot{token}/getUpdates',
            'method' => 'GET',
            'body' => 'none',
            'sort' => 5,
        ],
        [
            'name' => 'telegram_set_my_commands',
            'path' => 'bot{token}/setMyCommands',
            'method' => 'POST',
            'body' => 'json',
            'sort' => 6,
        ],
    ];

    /**
     * Field maps por endpoint.
     *
     * Los valores viajan como "override" desde runEndpoint($endpoint, $values),
     * por eso todos son estaticos y sin valor fijo: el servicio los inyecta en
     * cada llamada.
     *
     * @var array<string, array<int, array{key: string, location: string, required: bool, sort: int}>>
     */
    private const FIELD_MAPS = [
        'telegram_get_me' => [
            ['key' => 'token', 'location' => 'path', 'required' => true, 'sort' => 1],
        ],
        'telegram_send_message' => [
            ['key' => 'token', 'location' => 'path', 'required' => true, 'sort' => 1],
            ['key' => 'chat_id', 'location' => 'body', 'required' => true, 'sort' => 2],
            ['key' => 'text', 'location' => 'body', 'required' => true, 'sort' => 3],
        ],
        'telegram_set_webhook' => [
            ['key' => 'token', 'location' => 'path', 'required' => true, 'sort' => 1],
            ['key' => 'url', 'location' => 'body', 'required' => true, 'sort' => 2],
            ['key' => 'secret_token', 'location' => 'body', 'required' => true, 'sort' => 3],
            ['key' => 'drop_pending_updates', 'location' => 'body', 'required' => false, 'sort' => 4],
        ],
        'telegram_delete_webhook' => [
            ['key' => 'token', 'location' => 'path', 'required' => true, 'sort' => 1],
            ['key' => 'drop_pending_updates', 'location' => 'body', 'required' => false, 'sort' => 2],
        ],
        'telegram_get_updates' => [
            ['key' => 'token', 'location' => 'path', 'required' => true, 'sort' => 1],
        ],
        'telegram_set_my_commands' => [
            ['key' => 'token', 'location' => 'path', 'required' => true, 'sort' => 1],
            ['key' => 'commands', 'location' => 'body', 'required' => true, 'sort' => 2],
        ],
    ];

    /**
     * Idempotente: la integracion se busca por nombre, los endpoints por
     * (integracion, nombre) y los field maps por (endpoint, clave, ubicacion).
     * Si ya existen, solo se habilitan; nunca se duplican ni se sobreescriben
     * los valores que el administrador haya ajustado.
     */
    public function up(): void
    {
        if (! Schema::hasTable('integrations')
            || ! Schema::hasTable('integration_endpoints')
            || ! Schema::hasTable('integration_field_maps')) {
            return;
        }

        $integration = Integration::firstOrCreate(
            ['name' => self::INTEGRATION_NAME],
            [
                'url_base' => self::URL_BASE,
                'description' => 'Bot de Telegram para registro de chat_id y notificaciones de cursos',
                'execution_type' => 'manual',
                'is_active' => true,
                'config' => ['timeout' => 30],
            ]
        );

        foreach (self::ENDPOINTS as $endpoint) {
            $endpointModel = IntegrationEndpoint::updateOrCreate(
                [
                    'integration_id' => $integration->id,
                    'name' => $endpoint['name'],
                ],
                [
                    'endpoint_path' => $endpoint['path'],
                    'http_method' => $endpoint['method'],
                    'body_type' => $endpoint['body'],
                    'is_active' => true,
                    'sort_order' => $endpoint['sort'],
                ]
            );

            foreach (self::FIELD_MAPS[$endpoint['name']] ?? [] as $fieldMap) {
                IntegrationFieldMap::updateOrCreate(
                    [
                        'endpoint_id' => $endpointModel->id,
                        'field_key' => $fieldMap['key'],
                        'field_location' => $fieldMap['location'],
                    ],
                    [
                        'field_value' => '',
                        'field_type' => 'static',
                        'source_type' => 'static',
                        'source_table' => null,
                        'source_field' => null,
                        'default_value' => null,
                        'is_required' => $fieldMap['required'],
                        'is_enabled' => true,
                        'sort_order' => $fieldMap['sort'],
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('integrations')) {
            return;
        }

        $integration = Integration::where('name', self::INTEGRATION_NAME)->first();

        if (! $integration) {
            return;
        }

        $endpointIds = IntegrationEndpoint::where('integration_id', $integration->id)->pluck('id');

        if (Schema::hasTable('integration_field_maps') && $endpointIds->isNotEmpty()) {
            IntegrationFieldMap::whereIn('endpoint_id', $endpointIds)->delete();
        }

        IntegrationEndpoint::where('integration_id', $integration->id)->delete();
        $integration->delete();
    }
};
