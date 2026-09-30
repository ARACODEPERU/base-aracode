<?php

namespace Tests\Unit\Modules\Integrationhub;

use App\Models\Parameter;
use Modules\Integrationhub\Contracts\TelegramRegistrantResolver;
use Modules\Integrationhub\Entities\IntegrationError;
use Modules\Integrationhub\Entities\IntegrationTelegramContact;
use Modules\Integrationhub\Entities\IntegrationTelegramRegistrationSession;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;
use Modules\Integrationhub\Services\TelegramBotService;
use Modules\Integrationhub\Services\TelegramRegistrationService;
use Tests\TestCase;
use Tests\Unit\Modules\Integrationhub\Concerns\BuildsTelegramBotSchema;

/**
 * Webhook publico que recibe los mensajes del bot de Telegram.
 *
 * La promesa: solo procesa peticiones firmadas con el secreto derivado del
 * token; /start abre el registro y pide el documento; el siguiente mensaje se
 * resuelve contra el padron y, si la persona tiene programa o suscripcion, se
 * guarda su chat_id; /baja apaga el contacto; y los documentos errados se
 * cuentan hasta rendirse. Todo responde HTTP 200 (salvo el secreto invalido)
 * para que Telegram no reintente el update.
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
        $this->bindDirectory();

        $response = $this->postJson(self::URI, $this->message('/start'), [
            'X-Telegram-Bot-Api-Secret-Token' => 'secreto-malo',
        ]);

        $response->assertStatus(403);

        $this->assertSame([], $hub->calls);
        $this->assertSame(0, IntegrationTelegramRegistrationSession::count());
    }

    public function test_start_abre_el_registro_y_pide_el_documento(): void
    {
        $hub = $this->bindHub();
        $this->bindDirectory();

        $this->postJson(self::URI, $this->message('/start'), $this->secretHeader())->assertOk();

        $session = IntegrationTelegramRegistrationSession::where('chat_id', '555')->first();

        $this->assertNotNull($session);
        $this->assertSame('awaiting_document', $session->step);
        $this->assertSame('ana_tg', $session->telegram_username);

        $this->assertSame('telegram_send_message', $hub->calls[0]['endpoint']);
        $this->assertStringContainsString('documento', $hub->calls[0]['values']['text']);
    }

    public function test_start_ignora_cualquier_texto_que_venga_despues(): void
    {
        $hub = $this->bindHub();
        $this->bindDirectory();

        // Los enlaces personales que ya se repartieron siguen entrando al mismo
        // flujo: el codigo viejo se ignora y el bot pide el documento.
        $this->postJson(self::URI, $this->message('/start codigo-viejo'), $this->secretHeader())->assertOk();

        $this->assertNotNull(IntegrationTelegramRegistrationSession::where('chat_id', '555')->first());
        $this->assertStringContainsString('documento', $hub->calls[0]['values']['text']);
    }

    public function test_el_documento_del_padron_registra_el_chat(): void
    {
        $hub = $this->bindHub();
        $this->bindDirectory([
            '12345678' => [
                'person_id' => 100,
                'name' => 'Ana Pérez',
                'programs' => ['Especialización en NIIF'],
                'subscription' => false,
            ],
        ]);

        $this->postJson(self::URI, $this->message('/start'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message(' 12.345.678 '), $this->secretHeader())->assertOk();

        $contact = IntegrationTelegramContact::where('person_id', 100)->first();

        $this->assertNotNull($contact);
        $this->assertSame('555', $contact->chat_id);
        $this->assertSame('active', $contact->status);
        $this->assertSame(0, IntegrationTelegramRegistrationSession::count());

        $text = $hub->calls[1]['values']['text'];

        $this->assertStringContainsString('Ana Pérez', $text);
        $this->assertStringContainsString('Especialización en NIIF', $text);
    }

    public function test_quien_solo_tiene_suscripcion_tambien_se_registra(): void
    {
        $hub = $this->bindHub();
        $this->bindDirectory([
            '87654321' => [
                'person_id' => 101,
                'name' => 'Luis',
                'programs' => [],
                'subscription' => true,
            ],
        ]);

        $this->postJson(self::URI, $this->message('/start'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('87654321'), $this->secretHeader())->assertOk();

        $this->assertSame('555', IntegrationTelegramContact::where('person_id', 101)->value('chat_id'));
        $this->assertStringContainsString('suscripción', $hub->calls[1]['values']['text']);
    }

    public function test_un_documento_desconocido_se_reintenta_y_no_registra(): void
    {
        $hub = $this->bindHub();
        $this->bindDirectory();

        $this->postJson(self::URI, $this->message('/start'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('00000000'), $this->secretHeader())->assertOk();

        $this->assertSame(0, IntegrationTelegramContact::count());
        $this->assertSame(1, (int) IntegrationTelegramRegistrationSession::where('chat_id', '555')->value('attempts'));

        $text = $hub->calls[1]['values']['text'];

        $this->assertStringContainsString('No encontramos ese documento', $text);
        $this->assertStringContainsString('Intento 1 de 5', $text);

        // El documento no se guarda en la bitacora de errores.
        $error = IntegrationError::where('source', 'telegram_registration')->first();

        $this->assertNotNull($error);
        $this->assertStringNotContainsString('00000000', (string) $error->message);
    }

    public function test_un_texto_que_no_parece_documento_tambien_cuenta(): void
    {
        $hub = $this->bindHub();
        $this->bindDirectory();

        $this->postJson(self::URI, $this->message('/start'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('buenas'), $this->secretHeader())->assertOk();

        $this->assertSame(1, (int) IntegrationTelegramRegistrationSession::where('chat_id', '555')->value('attempts'));
        $this->assertStringContainsString('No reconocimos ese dato', $hub->calls[1]['values']['text']);
    }

    public function test_tras_cinco_intentos_se_cierra_la_conversacion(): void
    {
        $hub = $this->bindHub();
        $this->bindDirectory();

        $this->postJson(self::URI, $this->message('/start'), $this->secretHeader())->assertOk();

        foreach (range(1, 5) as $intento) {
            $this->postJson(self::URI, $this->message('00000000'), $this->secretHeader())->assertOk();
        }

        $this->assertSame(0, IntegrationTelegramRegistrationSession::count());
        $this->assertStringContainsString('Demasiados intentos', end($hub->calls)['values']['text']);
    }

    public function test_una_persona_sin_programa_ni_suscripcion_no_se_registra(): void
    {
        $hub = $this->bindHub();
        $this->bindDirectory([
            '11223344' => [
                'person_id' => 102,
                'name' => 'Sin Acceso',
                'programs' => [],
                'subscription' => false,
            ],
        ]);

        $this->postJson(self::URI, $this->message('/start'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('11223344'), $this->secretHeader())->assertOk();

        $this->assertSame(0, IntegrationTelegramContact::count());
        $this->assertSame(0, IntegrationTelegramRegistrationSession::count());
        $this->assertStringContainsString('no tiene un programa activo', $hub->calls[1]['values']['text']);
    }

    public function test_un_texto_suelto_sin_sesion_recibe_un_recordatorio_corto(): void
    {
        $hub = $this->bindHub();
        $this->bindDirectory();

        $this->postJson(self::URI, $this->message('hola?'), $this->secretHeader())->assertOk();

        $this->assertSame(0, IntegrationTelegramContact::count());
        $this->assertStringContainsString('/start', $hub->calls[0]['values']['text']);
        $this->assertStringNotContainsString('bot de avisos', $hub->calls[0]['values']['text']);
    }

    public function test_ayuda_explica_como_registrarse(): void
    {
        $hub = $this->bindHub();
        $this->bindDirectory();

        $this->postJson(self::URI, $this->message('/ayuda'), $this->secretHeader())->assertOk();

        $text = $hub->calls[0]['values']['text'];

        $this->assertStringContainsString('bot de avisos', $text);
        $this->assertStringContainsString('/start', $text);
    }

    public function test_sin_padron_disponible_el_registro_avisa_y_cierra_la_sesion(): void
    {
        $hub = $this->bindHub();

        // Un modulo sin padron vinculado (o que no se puede construir) no deja
        // el chat esperando: avisa y cierra la conversacion.
        $this->app->bind(TelegramRegistrantResolver::class, function () {
            throw new \RuntimeException('padron no disponible');
        });

        $this->postJson(self::URI, $this->message('/start'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('12345678'), $this->secretHeader())->assertOk();

        $this->assertSame(0, IntegrationTelegramRegistrationSession::count());
        $this->assertStringContainsString('no está habilitado', $hub->calls[1]['values']['text']);
    }

    public function test_baja_apaga_el_contacto_y_cierra_la_conversacion(): void
    {
        $hub = $this->bindHub();
        $this->bindDirectory();
        app(TelegramRegistrationService::class)->attach(101, '555');
        app(TelegramRegistrationService::class)->beginSession('555');

        $this->postJson(self::URI, $this->message('/baja'), $this->secretHeader())->assertOk();

        $this->assertSame('inactive', IntegrationTelegramContact::where('person_id', 101)->value('status'));
        $this->assertSame(0, IntegrationTelegramRegistrationSession::count());
        $this->assertStringContainsString('ya no recibirás', $hub->calls[0]['values']['text']);
    }

    public function test_baja_de_un_chat_no_registrado_lo_indica(): void
    {
        $hub = $this->bindHub();
        $this->bindDirectory();

        $this->postJson(self::URI, $this->message('/baja'), $this->secretHeader())->assertOk();

        $this->assertStringContainsString('no estaba registrado', $hub->calls[0]['values']['text']);
    }

    public function test_sin_token_configurado_el_webhook_no_hace_nada(): void
    {
        $hub = $this->bindHub();
        $this->bindDirectory();
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
     * Padron falso: cada documento declara la ficha que devolveria Academic.
     *
     * @param array<string, array{person_id: int, name: string, programs: array<int, string>, subscription: bool}> $people
     */
    private function bindDirectory(array $people = []): void
    {
        $this->app->instance(TelegramRegistrantResolver::class, new class($people) implements TelegramRegistrantResolver {
            /** @var array<string, array{person_id: int, name: string, programs: array<int, string>, subscription: bool}> */
            private array $people;

            /** @var array<int, string> */
            public array $asked = [];

            public function __construct(array $people)
            {
                $this->people = $people;
            }

            public function resolveByDocument(string $document): ?array
            {
                $this->asked[] = $document;

                return $this->people[$document] ?? null;
            }
        });
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
