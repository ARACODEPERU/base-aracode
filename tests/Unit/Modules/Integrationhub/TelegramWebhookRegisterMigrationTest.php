<?php

namespace Tests\Unit\Modules\Integrationhub;

use App\Models\Parameter;
use Modules\Integrationhub\Entities\Integration;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;
use Modules\Integrationhub\Services\TelegramBotService;
use Tests\TestCase;
use Tests\Unit\Modules\Integrationhub\Concerns\BuildsTelegramBotSchema;

/**
 * Migracion que registra el webhook del bot de Telegram al desplegar.
 *
 * La promesa: en vez de correr integrationhub:telegram-set-webhook a mano, la
 * migracion apunta el webhook a la URL publica de este entorno y publica el menu
 * de comandos; y, sobre todo, nunca tumba el despliegue: sin token o con
 * Telegram caido solo deja un aviso en el log. Es idempotente porque setWebhook
 * y setMyCommands sobreescriben con el mismo valor.
 *
 * Esta migracion no se suma a BuildsTelegramBotSchema a proposito: es una accion
 * de red y no debe dispararse en cada prueba del bot.
 */
class TelegramWebhookRegisterMigrationTest extends TestCase
{
    use BuildsTelegramBotSchema;

    private const TOKEN = '123456789:AA-Hr1-sometoken_xyz';

    private const MIGRATION = 'Modules/Integrationhub/Database/Migrations/2026_10_01_000002_register_telegram_bot_webhook.php';

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

    public function test_sin_token_no_registra_nada_y_no_falla(): void
    {
        $hub = $this->bindHub(fn () => response()->json(['response' => ['ok' => true]]));

        $this->runMigration();

        $this->assertSame([], $hub->calls);
    }

    public function test_con_token_registra_la_url_publica_y_publica_los_comandos(): void
    {
        $this->setToken(self::TOKEN);

        $hub = $this->bindHub(fn () => response()->json(['response' => ['ok' => true]]));

        $this->runMigration();

        $this->assertCount(2, $hub->calls);
        $this->assertSame('telegram_set_webhook', $hub->calls[0]['endpoint']);
        $this->assertStringEndsWith(
            '/api/integrationhub/telegram/webhook',
            $hub->calls[0]['values']['url']
        );
        $this->assertSame(app(TelegramBotService::class)->secret(), $hub->calls[0]['values']['secret_token']);
        $this->assertTrue($hub->calls[0]['values']['drop_pending_updates']);

        $this->assertSame('telegram_set_my_commands', $hub->calls[1]['endpoint']);
        $this->assertSame(
            app(TelegramBotService::class)->defaultCommands(),
            $hub->calls[1]['values']['commands']
        );
    }

    public function test_un_error_de_telegram_no_tumba_la_migracion(): void
    {
        $this->setToken(self::TOKEN);

        $hub = $this->bindHub(fn () => response()->json([
            'message' => 'Error en la solicitud externa',
            'response' => ['ok' => false, 'description' => 'Unauthorized'],
        ], 401));

        $this->runMigration();

        // La migracion termina sin lanzar y deja el aviso en el log.
        $this->assertCount(1, $hub->calls);
    }

    public function test_repetir_la_migracion_no_duplica_la_integracion(): void
    {
        $this->setToken(self::TOKEN);

        $hub = $this->bindHub(fn () => response()->json(['response' => ['ok' => true]]));

        $this->runMigration();
        $this->runMigration();

        $this->assertSame(1, Integration::where('name', 'Telegram_bot')->count());
        $this->assertCount(4, $hub->calls);
        $this->assertSame($hub->calls[0]['values']['url'], $hub->calls[2]['values']['url']);
        $this->assertSame($hub->calls[1]['values']['commands'], $hub->calls[3]['values']['commands']);
    }

    private function runMigration(): void
    {
        (require base_path(self::MIGRATION))->up();
    }

    private function setToken(?string $token): void
    {
        Parameter::where('parameter_code', 'SC-00002')->update(['value_default' => $token]);
    }

    /**
     * Doble del controlador de ejecucion: captura las llamadas y responde lo que
     * indique cada prueba, sin HTTP real.
     */
    private function bindHub(callable $responder): IntegrationhubController
    {
        $double = new class($responder) extends IntegrationhubController
        {
            public array $calls = [];

            /** @var callable */
            private $responder;

            public function __construct(callable $responder)
            {
                $this->responder = $responder;
            }

            public function runEndpoint(
                string $endpoint,
                array $fieldValues = [],
                array $extraParams = [],
                bool $trackResults = true,
                ?string $batchId = null
            ) {
                $this->calls[] = [
                    'endpoint' => $endpoint,
                    'values' => $fieldValues,
                    'track' => $trackResults,
                ];

                return ($this->responder)($endpoint, $fieldValues);
            }
        };

        $this->app->instance(IntegrationhubController::class, $double);

        return $double;
    }
}
