<?php

namespace Tests\Unit\Modules\Integrationhub;

use App\Models\Parameter;
use Modules\Integrationhub\Entities\IntegrationTelegramContact;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;
use Modules\Integrationhub\Services\TelegramBotService;
use Modules\Integrationhub\Services\TelegramRegistrationService;
use Tests\TestCase;
use Tests\Unit\Modules\Integrationhub\Concerns\BuildsTelegramBotSchema;

/**
 * Webhook publico que recibe los mensajes del bot de Telegram.
 *
 * La promesa: solo procesa peticiones firmadas con el secreto derivado del
 * token; /start <codigo> vincula el chat con la persona del enlace; /baja apaga
 * el contacto; y cualquier otro mensaje recibe ayuda. Todo responde HTTP 200
 * (salvo el secreto invalido) para que Telegram no reintente el update.
 */
class TelegramWebhookControllerTest extends TestCase
{
    use BuildsTelegramBotSchema;

    private const TOKEN = '123456789:AA-Hr1-sometoken_xyz';

    private const URI = '/api/integrationhub/telegram/webhook';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropTelegramSchema();
        $this->createTelegramSchema();

        Parameter::where('parameter_code', 'SC-00002')->update(['value_default' => self::TOKEN]);
    }

    protected function tearDown(): void
    {
        $this->dropTelegramSchema();

        parent::tearDown();
    }

    public function test_un_secreto_invalido_no_procesa_el_mensaje(): void
    {
        $hub = $this->bindHub();
        $contact = app(TelegramRegistrationService::class)->issueCode(100);

        $response = $this->postJson(self::URI, $this->message('/start ' . $contact->registration_code), [
            'X-Telegram-Bot-Api-Secret-Token' => 'secreto-malo',
        ]);

        $response->assertStatus(403);

        $this->assertSame([], $hub->calls);
        $this->assertNotNull($contact->fresh()->registration_code);
    }

    public function test_start_con_codigo_registra_el_chat_y_responde(): void
    {
        $hub = $this->bindHub();
        $contact = app(TelegramRegistrationService::class)->issueCode(100, 'Ana');
        $code = (string) $contact->registration_code;

        $response = $this->postJson(
            self::URI,
            $this->message('/start ' . $code, 'ana_tg'),
            $this->secretHeader()
        );

        $response->assertOk()->assertJson(['ok' => true]);

        $contact->refresh();

        $this->assertSame('555', $contact->chat_id);
        $this->assertSame('ana_tg', $contact->telegram_username);
        $this->assertNull($contact->registration_code);
        $this->assertNotNull($contact->registered_at);

        $this->assertSame('telegram_send_message', $hub->calls[0]['endpoint']);
        $this->assertSame('555', $hub->calls[0]['values']['chat_id']);
        $this->assertStringContainsString('registrado', $hub->calls[0]['values']['text']);
    }

    public function test_start_con_codigo_invalido_avisa_y_no_registra(): void
    {
        $hub = $this->bindHub();

        $response = $this->postJson(self::URI, $this->message('/start codigo-malo'), $this->secretHeader());

        $response->assertOk();
        $this->assertSame(0, IntegrationTelegramContact::count());
        $this->assertStringContainsString('no existe', $hub->calls[0]['values']['text']);
    }

    public function test_start_sin_codigo_explica_como_registrarse(): void
    {
        $hub = $this->bindHub();

        $this->postJson(self::URI, $this->message('/start'), $this->secretHeader())->assertOk();

        $this->assertStringContainsString('enlace de registro', $hub->calls[0]['values']['text']);
    }

    public function test_baja_apaga_el_contacto(): void
    {
        $hub = $this->bindHub();
        app(TelegramRegistrationService::class)->attach(101, '555');

        $this->postJson(self::URI, $this->message('/baja'), $this->secretHeader())->assertOk();

        $this->assertSame('inactive', IntegrationTelegramContact::where('person_id', 101)->value('status'));
        $this->assertStringContainsString('ya no recibirás', $hub->calls[0]['values']['text']);
    }

    public function test_baja_de_un_chat_no_registrado_lo_indica(): void
    {
        $hub = $this->bindHub();

        $this->postJson(self::URI, $this->message('/baja'), $this->secretHeader())->assertOk();

        $this->assertStringContainsString('no estaba registrado', $hub->calls[0]['values']['text']);
    }

    public function test_cualquier_otro_mensaje_recibe_ayuda(): void
    {
        $hub = $this->bindHub();

        $this->postJson(self::URI, $this->message('hola?'), $this->secretHeader())->assertOk();

        $this->assertStringContainsString('bot de avisos', $hub->calls[0]['values']['text']);
    }

    public function test_sin_token_configurado_el_webhook_no_hace_nada(): void
    {
        $hub = $this->bindHub();
        Parameter::where('parameter_code', 'SC-00002')->update(['value_default' => null]);

        $response = $this->postJson(self::URI, $this->message('/start'), $this->secretHeader());

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertSame([], $hub->calls);
    }

    /**
     * @return array<string, mixed>
     */
    private function message(string $text, string $username = 'ana_tg', string $chatId = '555'): array
    {
        return [
            'update_id' => 1,
            'message' => [
                'message_id' => 5,
                'chat' => ['id' => $chatId, 'type' => 'private'],
                'from' => ['id' => $chatId, 'username' => $username, 'first_name' => 'Ana'],
                'text' => $text,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function secretHeader(): array
    {
        return ['X-Telegram-Bot-Api-Secret-Token' => (string) app(TelegramBotService::class)->secret()];
    }

    /**
     * Doble del controlador de ejecucion: captura los envios sin hacer HTTP.
     */
    private function bindHub(): IntegrationhubController
    {
        $double = new class extends IntegrationhubController {
            public array $calls = [];

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
                ];

                return response()->json(['response' => ['ok' => true, 'result' => ['message_id' => 1]]]);
            }
        };

        $this->app->instance(IntegrationhubController::class, $double);

        return $double;
    }
}
