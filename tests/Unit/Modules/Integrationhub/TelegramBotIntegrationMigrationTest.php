<?php

namespace Tests\Unit\Modules\Integrationhub;

use App\Models\Parameter;
use Modules\Integrationhub\Entities\Integration;
use Modules\Integrationhub\Entities\IntegrationEndpoint;
use Modules\Integrationhub\Entities\IntegrationFieldMap;
use Illuminate\Support\Facades\Schema;
use Modules\Integrationhub\Entities\IntegrationTelegramContact;
use Tests\TestCase;
use Tests\Unit\Modules\Integrationhub\Concerns\BuildsTelegramBotSchema;

/**
 * Migraciones del bot de Telegram.
 *
 * La promesa: la integración "Telegram_bot" nace con sus seis endpoints y sus
 * field maps (token en la ruta, chat_id y texto en el body); las tablas del bot
 * existen (contactos y sesiones de registro) y quedan sin las columnas del
 * registro con código personal; y, sobre todo, las migraciones se pueden
 * re-ejecutar sin duplicar nada ni pisar lo que el administrador ya configuró
 * (incluido el token del parámetro SC-00002).
 */
class TelegramBotIntegrationMigrationTest extends TestCase
{
    use BuildsTelegramBotSchema;

    private const INTEGRATION_MIGRATION = 'Modules/Integrationhub/Database/Migrations/2026_09_30_000011_create_telegram_bot_integration.php';

    private const CONTACTS_MIGRATION = 'Modules/Integrationhub/Database/Migrations/2026_09_30_000010_create_integration_telegram_contacts_table.php';

    private const PARAMETER_MIGRATION = 'database/migrations/2026_09_30_000001_add_telegram_bot_token_parameter.php';

    private const SESSIONS_MIGRATION = 'Modules/Integrationhub/Database/Migrations/2026_09_30_000012_create_telegram_registration_sessions_table.php';

    private const CLEANUP_MIGRATION = 'Modules/Integrationhub/Database/Migrations/2026_09_30_000013_drop_registration_code_from_telegram_contacts.php';

    private const PARSE_MODE_MIGRATION = 'Modules/Integrationhub/Database/Migrations/2026_09_30_000014_add_parse_mode_to_telegram_send_message.php';

    private const MESSAGES_MIGRATION = 'Modules/Integrationhub/Database/Migrations/2026_09_30_000015_create_integration_telegram_messages_table.php';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropTelegramSchema();
        $this->createTelegramSchema();
    }

    protected function tearDown(): void
    {
        $this->dropTelegramSchema();

        parent::tearDown();
    }

    public function test_crea_la_integracion_con_sus_endpoints_y_field_maps(): void
    {
        $integration = Integration::where('name', 'Telegram_bot')->first();

        $this->assertNotNull($integration);
        $this->assertSame('https://api.telegram.org', $integration->url_base);

        $this->assertSame(
            [
                'telegram_get_me',
                'telegram_send_message',
                'telegram_set_webhook',
                'telegram_delete_webhook',
                'telegram_get_updates',
                'telegram_set_my_commands',
            ],
            $integration->endpoints()->orderBy('sort_order')->pluck('name')->all()
        );

        $send = $integration->endpoints()->where('name', 'telegram_send_message')->first();

        $this->assertSame('POST', $send->http_method);
        $this->assertSame('bot{token}/sendMessage', $send->endpoint_path);

        $maps = $send->fieldMaps()->get()->keyBy('field_key');

        // El token viaja en la ruta y el resto en el cuerpo JSON.
        $this->assertSame('path', $maps['token']->field_location);
        $this->assertTrue((bool) $maps['token']->is_required);
        $this->assertSame('body', $maps['chat_id']->field_location);
        $this->assertTrue((bool) $maps['chat_id']->is_required);
        $this->assertSame('body', $maps['text']->field_location);
        $this->assertTrue((bool) $maps['text']->is_required);

        // El formato viaja fijo en HTML: el texto sale escapado desde PHP (ver
        // Support\TelegramHtml), asi que Telegram nunca recibe simbolos sueltos.
        $this->assertSame('body', $maps['parse_mode']->field_location);
        $this->assertSame('HTML', $maps['parse_mode']->field_value);
        $this->assertFalse((bool) $maps['parse_mode']->is_required);
        $this->assertTrue((bool) $maps['parse_mode']->is_enabled);
    }

    public function test_crea_el_parametro_del_token_y_las_tablas_del_bot(): void
    {
        $this->assertTrue(Schema::hasTable('integration_telegram_contacts'));
        $this->assertTrue(Schema::hasTable('integration_telegram_registration_sessions'));
        $this->assertTrue(Schema::hasTable('integration_telegram_messages'));

        $parameter = Parameter::where('parameter_code', 'SC-00002')->first();

        $this->assertNotNull($parameter);
        $this->assertNull($parameter->value_default);
        $this->assertStringContainsString('Telegram', $parameter->description);

        $this->assertInstanceOf(IntegrationTelegramContact::class, new IntegrationTelegramContact());
    }

    public function test_retira_las_columnas_del_registro_con_codigo(): void
    {
        // El registro ahora es con el enlace unico del bot y el documento que la
        // persona escribe: los codigos personales ya no tienen columnas.
        $this->assertFalse(Schema::hasColumn('integration_telegram_contacts', 'registration_code'));
        $this->assertFalse(Schema::hasColumn('integration_telegram_contacts', 'code_expires_at'));
    }

    public function test_repetir_las_migraciones_no_duplica_nada(): void
    {
        $parameterTotal = Parameter::count();

        $this->rerunTelegramMigration(self::PARAMETER_MIGRATION);
        $this->rerunTelegramMigration(self::CONTACTS_MIGRATION);
        $this->rerunTelegramMigration(self::INTEGRATION_MIGRATION);
        $this->rerunTelegramMigration(self::SESSIONS_MIGRATION);
        $this->rerunTelegramMigration(self::CLEANUP_MIGRATION);
        $this->rerunTelegramMigration(self::PARSE_MODE_MIGRATION);
        $this->rerunTelegramMigration(self::MESSAGES_MIGRATION);

        $integration = Integration::where('name', 'Telegram_bot')->first();

        $this->assertSame(1, Integration::where('name', 'Telegram_bot')->count());
        $this->assertSame(6, IntegrationEndpoint::where('integration_id', $integration->id)->count());
        $this->assertSame(14, IntegrationFieldMap::count());
        $this->assertSame(1, Parameter::where('parameter_code', 'SC-00002')->count());
        $this->assertSame($parameterTotal, Parameter::count());
    }

    public function test_repetir_la_migracion_del_formato_respeta_lo_configurado(): void
    {
        $endpoint = IntegrationEndpoint::where('name', 'telegram_send_message')->first();
        $field = IntegrationFieldMap::where('endpoint_id', $endpoint->id)->where('field_key', 'parse_mode')->first();
        $field->update(['field_value' => 'MarkdownV2']);

        $this->rerunTelegramMigration(self::PARSE_MODE_MIGRATION);

        $this->assertSame(
            'MarkdownV2',
            IntegrationFieldMap::where('endpoint_id', $endpoint->id)->where('field_key', 'parse_mode')->value('field_value')
        );
        $this->assertSame(1, IntegrationFieldMap::where('endpoint_id', $endpoint->id)->where('field_key', 'parse_mode')->count());
    }

    public function test_repetir_la_migracion_respeta_el_token_configurado(): void
    {
        Parameter::where('parameter_code', 'SC-00002')->update(['value_default' => '123456789:AA-token-real']);

        $this->rerunTelegramMigration(self::PARAMETER_MIGRATION);

        $this->assertSame(
            '123456789:AA-token-real',
            Parameter::where('parameter_code', 'SC-00002')->value('value_default')
        );
    }
}
