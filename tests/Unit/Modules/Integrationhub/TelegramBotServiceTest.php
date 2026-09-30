<?php

namespace Tests\Unit\Modules\Integrationhub;

use App\Models\Parameter;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;
use Modules\Integrationhub\Services\TelegramBotService;
use RuntimeException;
use Tests\TestCase;
use Tests\Unit\Modules\Integrationhub\Concerns\BuildsTelegramBotSchema;

/**
 * El servicio que habla con la API de Telegram a traves de Integrationhub.
 *
 * La promesa: el bot solo existe si el parametro SC-00002 tiene token; el token
 * viaja como variable de ruta en cada endpoint; el secreto del webhook se
 * deriva del token (no hace falta otro parametro); y un rechazo de Telegram
 * (HTTP 400 o {"ok":false}) se convierte en excepcion, no en un exito silencioso.
 */
class TelegramBotServiceTest extends TestCase
{
    use BuildsTelegramBotSchema;

    private const TOKEN = '123456789:AA-Hr1-sometoken_xyz';

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

    public function test_sin_token_el_bot_no_esta_configurado(): void
    {
        $this->assertFalse(app(TelegramBotService::class)->isConfigured());
        $this->assertNull(app(TelegramBotService::class)->token());
        $this->assertNull(app(TelegramBotService::class)->secret());
    }

    public function test_con_token_esta_configurado_y_el_secreto_se_deriva_del_token(): void
    {
        $this->setToken(self::TOKEN);

        $bot = app(TelegramBotService::class);

        $this->assertTrue($bot->isConfigured());
        $this->assertSame(self::TOKEN, $bot->token());

        $secret = $bot->secret();

        $this->assertNotNull($secret);
        $this->assertNotSame(self::TOKEN, $secret);
        $this->assertSame($secret, app(TelegramBotService::class)->secret());

        // Cambiar el token cambia el secreto: el webhook debe registrarse de nuevo.
        $this->setToken('999:otro-token');
        $this->assertNotSame($secret, app(TelegramBotService::class)->secret());
    }

    public function test_send_message_inyecta_el_token_en_la_ruta_y_el_texto_en_el_body(): void
    {
        $this->setToken(self::TOKEN);

        $hub = $this->bindHub(fn (string $endpoint, array $values) => response()->json([
            'response' => [
                'ok' => true,
                'result' => ['message_id' => 9, 'chat' => ['id' => $values['chat_id']]],
            ],
        ]));

        $result = app(TelegramBotService::class)->sendMessage('555123', 'Hola alumno');

        $this->assertSame('telegram_send_message', $hub->calls[0]['endpoint']);
        $this->assertSame(self::TOKEN, $hub->calls[0]['values']['token']);
        $this->assertSame('555123', $hub->calls[0]['values']['chat_id']);
        $this->assertSame('Hola alumno', $hub->calls[0]['values']['text']);
        $this->assertSame(9, $result['result']['message_id']);
    }

    public function test_sin_token_no_se_puede_enviar(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Falta configurar el token del bot de Telegram');

        app(TelegramBotService::class)->sendMessage('555123', 'Hola');
    }

    public function test_telegram_con_ok_false_lanza_excepcion(): void
    {
        $this->setToken(self::TOKEN);

        $this->bindHub(fn () => response()->json([
            'response' => ['ok' => false, 'description' => 'chat not found'],
        ]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('chat not found');

        app(TelegramBotService::class)->sendMessage('555123', 'Hola');
    }

    public function test_un_error_http_lanza_excepcion(): void
    {
        $this->setToken(self::TOKEN);

        $this->bindHub(fn () => response()->json(['message' => 'Error en la solicitud externa: Unauthorized'], 401));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HTTP 401');

        app(TelegramBotService::class)->sendMessage('555123', 'Hola');
    }

    public function test_arma_el_enlace_de_registro_con_el_usuario_del_bot(): void
    {
        $this->setToken(self::TOKEN);

        $hub = $this->bindHub(fn (string $endpoint) => response()->json([
            'response' => ['ok' => true, 'result' => ['username' => 'cpa_academy_bot']],
        ]));

        $link = app(TelegramBotService::class)->registrationLink('ABC123');

        $this->assertSame('https://t.me/cpa_academy_bot?start=ABC123', $link);
        $this->assertSame('telegram_get_me', $hub->calls[0]['endpoint']);
        // getMe se consulta sin registrar bitacora: es solo un dato de apoyo.
        $this->assertFalse($hub->calls[0]['track']);
    }

    public function test_registra_el_webhook_con_el_secreto_derivado(): void
    {
        $this->setToken(self::TOKEN);

        $hub = $this->bindHub(fn () => response()->json(['response' => ['ok' => true]]));

        $bot = app(TelegramBotService::class);
        $bot->setWebhook('https://midominio.com/api/integrationhub/telegram/webhook', true);

        $this->assertSame('telegram_set_webhook', $hub->calls[0]['endpoint']);
        $this->assertSame($bot->secret(), $hub->calls[0]['values']['secret_token']);
        $this->assertTrue($hub->calls[0]['values']['drop_pending_updates']);
        $this->assertStringEndsWith(
            '/api/integrationhub/telegram/webhook',
            $hub->calls[0]['values']['url']
        );
    }

    public function test_la_url_del_webhook_apunta_a_la_ruta_publica(): void
    {
        $this->setToken(self::TOKEN);

        $this->assertStringEndsWith(
            '/api/integrationhub/telegram/webhook',
            app(TelegramBotService::class)->webhookUrl()
        );
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
        $double = new class($responder) extends IntegrationhubController {
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
