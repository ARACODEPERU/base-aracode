<?php

namespace Tests\Unit\Modules\Integrationhub;

use App\Models\Parameter;
use Illuminate\Support\Facades\DB;
use Modules\Integrationhub\Contracts\TelegramAccountResolver;
use Modules\Integrationhub\Contracts\TelegramRegistrantResolver;
use Modules\Integrationhub\Entities\IntegrationError;
use Modules\Integrationhub\Entities\IntegrationTelegramContact;
use Modules\Integrationhub\Entities\IntegrationTelegramRegistrationSession;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;
use Modules\Integrationhub\Services\TelegramBotService;
use Modules\Integrationhub\Services\TelegramMessageService;
use Modules\Integrationhub\Services\TelegramRegistrationService;
use Modules\Integrationhub\Support\TelegramMessages;
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
        $this->assertStringContainsString('Suscripción activa', $hub->calls[1]['values']['text']);
        // Sin programas, su linea no se envia.
        $this->assertStringNotContainsString('Programas:', $hub->calls[1]['values']['text']);
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
        $this->assertStringContainsString('No reconocí eso', $hub->calls[1]['values']['text']);
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

    public function test_start_en_un_chat_ya_registrado_menciona_al_titular(): void
    {
        $hub = $this->bindHub();
        $this->bindDirectory();

        DB::table('people')->insert([
            'id' => 100,
            'short_name' => 'Ana',
            'full_name' => 'Ana Pérez',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(TelegramRegistrationService::class)->attach(100, '555');

        $this->postJson(self::URI, $this->message('/start'), $this->secretHeader())->assertOk();

        $text = $hub->calls[0]['values']['text'];

        $this->assertStringContainsString('Ana', $text);
        $this->assertStringContainsString('ya está registrado', $text);
    }

    public function test_el_bot_usa_el_texto_configurado_por_el_administrador(): void
    {
        $hub = $this->bindHub();
        $this->bindDirectory();

        // El administrador reescribe la ayuda desde la pantalla de Notificaciones:
        // el bot debe usar ese texto, con sus simbolos escapados para Telegram.
        app(TelegramMessageService::class)->save(
            TelegramMessages::HELP,
            'Avisos & <b>novedades</b> de la institución',
            TelegramMessages::FORMAT_HTML
        );

        $this->postJson(self::URI, $this->message('/ayuda'), $this->secretHeader())->assertOk();

        $this->assertSame('Avisos &amp; <b>novedades</b> de la institución', $hub->calls[0]['values']['text']);
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

    public function test_chatid_devuelve_el_identificador_del_chat(): void
    {
        $hub = $this->bindHub();
        $this->bindDirectory();

        $this->postJson(self::URI, $this->message('/chatid'), $this->secretHeader())->assertOk();

        $this->assertStringContainsString('555', $hub->calls[0]['values']['text']);
    }

    public function test_la_consulta_de_cursos_pide_correo_y_documento_y_lista(): void
    {
        $hub = $this->bindHub();
        $this->bindAccountDirectory([
            '12345678' => [
                'email' => 'ana@correo.com',
                'account' => [
                    'person_id' => 100,
                    'name' => 'Ana Pérez',
                    'courses' => [
                        ['description' => 'Curso pagado', 'type' => 'Cursos', 'time_limit' => 'Acceso ilimitado'],
                    ],
                    'subscription' => null,
                    'certificates' => [],
                ],
            ],
        ]);

        $this->postJson(self::URI, $this->message('/cursos'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('ana@correo.com'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('12345678'), $this->secretHeader())->assertOk();

        $this->assertStringContainsString('correo electrónico', $hub->calls[0]['values']['text']);
        $this->assertStringContainsString('documento', $hub->calls[1]['values']['text']);
        $this->assertStringContainsString('Curso pagado', $hub->calls[2]['values']['text']);
        $this->assertSame(0, IntegrationTelegramRegistrationSession::count());
    }

    public function test_la_consulta_avisa_cuando_los_datos_no_coinciden(): void
    {
        $hub = $this->bindHub();
        $this->bindAccountDirectory([
            '12345678' => [
                'email' => 'ana@correo.com',
                'account' => [
                    'person_id' => 100,
                    'name' => 'Ana',
                    'courses' => [],
                    'subscription' => null,
                    'certificates' => [],
                ],
            ],
        ]);

        $this->postJson(self::URI, $this->message('/cursos'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('otro@correo.com'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('12345678'), $this->secretHeader())->assertOk();

        $this->assertStringContainsString('no coinciden', $hub->calls[2]['values']['text']);
        $this->assertSame(1, (int) IntegrationTelegramRegistrationSession::where('chat_id', '555')->value('attempts'));

        // La credencial probada no se guarda en la bitacora.
        $error = IntegrationError::where('source', 'telegram_query')->first();

        $this->assertNotNull($error);
        $this->assertStringNotContainsString('12345678', (string) $error->message);
        $this->assertStringNotContainsString('otro@correo.com', (string) $error->message);
    }

    public function test_un_correo_invalido_cuenta_intento(): void
    {
        $hub = $this->bindHub();
        $this->bindAccountDirectory();

        $this->postJson(self::URI, $this->message('/cursos'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('no-es-un-correo'), $this->secretHeader())->assertOk();

        $this->assertStringContainsString('No reconocí eso como un correo', $hub->calls[1]['values']['text']);
        $this->assertSame(1, (int) IntegrationTelegramRegistrationSession::where('chat_id', '555')->value('attempts'));
    }

    public function test_tras_cinco_intentos_se_cierra_la_consulta(): void
    {
        $hub = $this->bindHub();
        $this->bindAccountDirectory();

        $this->postJson(self::URI, $this->message('/cursos'), $this->secretHeader())->assertOk();

        foreach (range(1, 5) as $intento) {
            $this->postJson(self::URI, $this->message('no-es-un-correo'), $this->secretHeader())->assertOk();
        }

        $this->assertSame(0, IntegrationTelegramRegistrationSession::count());
        $this->assertStringContainsString('Demasiados intentos', end($hub->calls)['values']['text']);
    }

    public function test_la_consulta_informa_la_suscripcion_sin_especializacion(): void
    {
        $hub = $this->bindHub();
        $this->bindAccountDirectory([
            '12345678' => [
                'email' => 'ana@correo.com',
                'account' => [
                    'person_id' => 100,
                    'name' => 'Ana',
                    'courses' => [],
                    'subscription' => ['vip' => false, 'ends_at' => null],
                    'certificates' => [],
                ],
            ],
        ]);

        $this->postJson(self::URI, $this->message('/cursos'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('ana@correo.com'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('12345678'), $this->secretHeader())->assertOk();

        $text = $hub->calls[2]['values']['text'];

        $this->assertStringContainsString('excepto los Programas de Especialización', $text);
        // Sin fecha de vigencia, su linea no se envia.
        $this->assertStringNotContainsString('Vigencia:', $text);
    }

    public function test_la_consulta_informa_la_suscripcion_premium_vip(): void
    {
        $hub = $this->bindHub();
        $this->bindAccountDirectory([
            '12345678' => [
                'email' => 'ana@correo.com',
                'account' => [
                    'person_id' => 100,
                    'name' => 'Ana',
                    'courses' => [],
                    'subscription' => ['vip' => true, 'ends_at' => '2026-12-31'],
                    'certificates' => [],
                ],
            ],
        ]);

        $this->postJson(self::URI, $this->message('/cursos'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('ana@correo.com'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('12345678'), $this->secretHeader())->assertOk();

        $text = $hub->calls[2]['values']['text'];

        $this->assertStringContainsString('Premium VIP', $text);
        $this->assertStringContainsString('Programas de Especialización', $text);
        $this->assertStringContainsString('2026-12-31', $text);
    }

    public function test_certificados_lista_sin_descarga_y_manda_a_la_plataforma(): void
    {
        $hub = $this->bindHub();
        $this->bindAccountDirectory([
            '12345678' => [
                'email' => 'ana@correo.com',
                'account' => [
                    'person_id' => 100,
                    'name' => 'Ana',
                    'courses' => [],
                    'subscription' => null,
                    'certificates' => [
                        [
                            'id' => 7,
                            'course' => 'Especialización',
                            'module' => null,
                        ],
                    ],
                    'platform_url' => 'https://ejemplo.test/login',
                ],
            ],
        ]);

        $this->postJson(self::URI, $this->message('/certificados'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('ana@correo.com'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('12345678'), $this->secretHeader())->assertOk();

        $text = $hub->calls[2]['values']['text'];

        $this->assertStringContainsString('Especialización', $text);
        $this->assertStringContainsString('ingresa a la plataforma', $text);
        $this->assertStringContainsString('https://ejemplo.test/login', $text);

        // El bot no entrega el certificado: ni el archivo ni un enlace directo.
        $this->assertStringNotContainsString('Descargar certificado', $text);
        $this->assertStringNotContainsString('/download', $text);
    }

    public function test_start_menciona_todas_las_opciones_del_bot(): void
    {
        $hub = $this->bindHub();
        $this->bindDirectory();

        $this->postJson(self::URI, $this->message('/start'), $this->secretHeader())->assertOk();

        $text = $hub->calls[0]['values']['text'];

        foreach (['/start', '/cursos', '/certificados', '/chatid', '/ayuda', '/baja'] as $command) {
            $this->assertStringContainsString($command, $text);
        }
    }

    public function test_sin_padron_de_consulta_el_bot_avisa(): void
    {
        $hub = $this->bindHub();

        $this->app->bind(TelegramAccountResolver::class, function () {
            throw new \RuntimeException('padron no disponible');
        });

        $this->postJson(self::URI, $this->message('/cursos'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('ana@correo.com'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('12345678'), $this->secretHeader())->assertOk();

        $this->assertSame(0, IntegrationTelegramRegistrationSession::count());
        $this->assertStringContainsString('no está habilitada', $hub->calls[2]['values']['text']);
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
     * Padron falso de consulta: cada documento declara su correo y la ficha.
     *
     * Devuelve null cuando el correo no coincide, igual que la implementacion
     * real, para poder probar el mensaje generico de datos que no coinciden.
     *
     * @param array<string, array{email: string, account: array<string, mixed>}> $people
     */
    private function bindAccountDirectory(array $people = []): void
    {
        $this->app->instance(TelegramAccountResolver::class, new class($people) implements TelegramAccountResolver {
            /** @var array<string, array{email: string, account: array<string, mixed>}> */
            private array $people;

            public function __construct(array $people)
            {
                $this->people = $people;
            }

            public function resolveAccount(string $document, string $email): ?array
            {
                $person = $this->people[$document] ?? null;

                if ($person === null) {
                    return null;
                }

                if (strcasecmp(trim($person['email']), trim($email)) !== 0) {
                    return null;
                }

                return $person['account'];
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
