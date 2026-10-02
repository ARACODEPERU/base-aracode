<?php

namespace Tests\Unit\Modules\Academic;

use App\Models\Parameter;
use App\Services\JobOffersAccess;
use Illuminate\Support\Facades\DB;
use Modules\Integrationhub\Contracts\TelegramRegistrantResolver;
use Modules\Integrationhub\Entities\IntegrationTelegramContact;
use Modules\Integrationhub\Entities\IntegrationTelegramRegistrationSession;
use Modules\Integrationhub\Http\Controllers\IntegrationhubController;
use Modules\Integrationhub\Services\TelegramBotService;
use Tests\TestCase;
use Tests\Unit\Modules\Academic\Concerns\BuildsTelegramStudentSchema;
use Tests\Unit\Modules\Integrationhub\Concerns\BuildsTelegramBotSchema;

/**
 * Registro del bot de punta a punta, con el padron academico real.
 *
 * A diferencia de las pruebas del webhook (que usan un padron falso), aqui se
 * deja actuar el binding que registra AcademicServiceProvider: el chat escribe
 * /start y su documento, y el mensaje que se guarda tiene que salir del padron
 * de verdad (persona, alumno, matricula y curso).
 */
class TelegramStudentRegistrationFlowTest extends TestCase
{
    use BuildsTelegramBotSchema;
    use BuildsTelegramStudentSchema;

    private const TOKEN = '123456789:AA-Hr1-sometoken_xyz';

    private const URI = '/api/integrationhub/telegram/webhook';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropTelegramSchema();
        $this->dropTelegramStudentSchema();

        // El padron academico primero: su tabla de personas es la completa.
        $this->createTelegramStudentSchema();
        $this->createTelegramSchema();

        Parameter::where('parameter_code', 'SC-00002')->update(['value_default' => self::TOKEN]);
    }

    protected function tearDown(): void
    {
        $this->dropTelegramSchema();
        $this->dropTelegramStudentSchema();

        parent::tearDown();
    }

    public function test_el_padron_academico_esta_vinculado_al_webhook(): void
    {
        $this->assertTrue(app()->bound(TelegramRegistrantResolver::class));
        $this->assertInstanceOf(
            TelegramRegistrantResolver::class,
            app(TelegramRegistrantResolver::class)
        );
    }

    public function test_un_alumno_matriculado_se_registra_escribiendo_su_documento(): void
    {
        $hub = $this->bindHub();
        $studentId = $this->student('12345678', 'Ana Pérez');
        $this->course('Especialización en NIIF', JobOffersAccess::SPECIALIZATION_TYPE);
        $this->enroll($studentId, 1);

        $this->postJson(self::URI, $this->message('/start'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('12345678'), $this->secretHeader())->assertOk();

        $contact = IntegrationTelegramContact::where('chat_id', '555')->first();

        $this->assertNotNull($contact);
        $this->assertSame(1, (int) $contact->person_id);
        $this->assertSame('active', $contact->status);
        $this->assertSame(0, IntegrationTelegramRegistrationSession::count());

        $text = $hub->calls[1]['values']['text'];

        $this->assertStringContainsString('Ana Pérez', $text);
        $this->assertStringContainsString('Especialización en NIIF', $text);
    }

    public function test_un_documento_que_no_esta_matriculado_no_registra_nada(): void
    {
        $hub = $this->bindHub();
        $this->student('87654321', 'Luis');

        $this->postJson(self::URI, $this->message('/start'), $this->secretHeader())->assertOk();
        $this->postJson(self::URI, $this->message('87654321'), $this->secretHeader())->assertOk();

        $this->assertSame(0, IntegrationTelegramContact::count());
        $this->assertStringContainsString('no tiene un programa activo', $hub->calls[1]['values']['text']);
    }

    /**
     * @return array<string, mixed>
     */
    private function message(string $text): array
    {
        return [
            'update_id' => 1,
            'message' => [
                'message_id' => 5,
                'chat' => ['id' => '555', 'type' => 'private'],
                'from' => ['id' => '555', 'username' => 'ana_tg', 'first_name' => 'Ana'],
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

    private function student(string $document, ?string $name): int
    {
        $personId = DB::table('people')->insertGetId([
            'short_name' => $name,
            'full_name' => $name,
            'number' => $document,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('aca_students')->insertGetId([
            'person_id' => $personId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function course(string $description, string $type, int $id = 0): int
    {
        if ($id > 0) {
            DB::table('aca_courses')->insert([
                'id' => $id,
                'description' => $description,
                'type_description' => $type,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $id;
        }

        return (int) DB::table('aca_courses')->insertGetId([
            'description' => $description,
            'type_description' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function enroll(int $studentId, int $courseId): void
    {
        DB::table('aca_cap_registrations')->insert([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
