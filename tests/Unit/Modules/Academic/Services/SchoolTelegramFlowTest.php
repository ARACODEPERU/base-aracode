<?php

namespace Tests\Unit\Modules\Academic\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\Academic\Entities\AcaSchoolNoticeDelivery;
use Modules\Academic\Entities\AcaSchoolTelegramSubscription;
use Modules\Academic\Http\Controllers\Api\SchoolTelegramWebhookController;
use Modules\Academic\Jobs\SendSchoolGateNotice;
use Modules\Academic\Services\SchoolGateNoticeService;
use Modules\Academic\Services\SchoolTelegramBotService;
use Modules\Academic\Services\SchoolTelegramNoticeService;
use Modules\Academic\Services\SchoolTelegramRegistrationService;
use Tests\TestCase;
use Tests\Unit\Modules\Academic\Concerns\BuildsSchoolNoticeSchema;

/**
 * Pruebas del canal Telegram escolar (registro de apoderado + 1 o más hijos,
 * comandos /registrar, /baja, /chatid, /info, y envío de avisos de entrada y salida).
 */
class SchoolTelegramFlowTest extends TestCase
{
    use BuildsSchoolNoticeSchema;

    private const TOKEN = '7123456789:AAH_test_school_token';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropSchoolNoticeSchema();
        $this->createSchoolNoticeSchema();

        $this->setChannelParameter('SC-00033', self::TOKEN);
        $this->setChannelParameter('SC-00034', '1');
        $this->setChannelParameter('SC-00035', '1');
        $this->setChannelParameter('SC-00036', '1');
        $this->setChannelParameter('SC-00037', '1');
        $this->setChannelParameter('SC-00038', '1');
        $this->setChannelParameter('SC-00041', '0');
        $this->setChannelParameter('SC-00042', '0');
        $this->setChannelParameter('SC-00043', '0');
    }

    protected function tearDown(): void
    {
        $this->dropSchoolNoticeSchema();

        parent::tearDown();
    }

    public function test_flujo_completo_de_registro_de_apoderado_con_dos_hijos_y_baja(): void
    {
        $sentMessages = [];

        Http::fake(function ($request) use (&$sentMessages) {
            $sentMessages[] = $request->data();

            return Http::response(['ok' => true, 'result' => ['message_id' => count($sentMessages)]], 200);
        });

        // Creamos colegio, estructura, un padre y dos hijos estudiantes.
        DB::table('aca_schools')->insert(['id' => 1, 'name' => 'I.E. San Martín', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('aca_school_grades')->insert(['id' => 1, 'name' => 'Primaria 3°', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('aca_school_sections')->insert(['id' => 1, 'school_id' => 1, 'grade_id' => 1, 'name' => 'A', 'created_at' => now(), 'updated_at' => now()]);

        $guardianPersonId = (int) DB::table('people')->insertGetId([
            'full_name' => 'Carlos Gómez',
            'number' => '40506070',
            'telephone' => '987654321',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $child1PersonId = (int) DB::table('people')->insertGetId([
            'full_name' => 'Mateo Gómez',
            'number' => '70809011',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $child2PersonId = (int) DB::table('people')->insertGetId([
            'full_name' => 'Lucía Gómez',
            'number' => '70809022',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $student1Id = (int) DB::table('aca_school_students')->insertGetId([
            'school_id' => 1,
            'person_id' => $child1PersonId,
            'student_code' => '2026-0001',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $student2Id = (int) DB::table('aca_school_students')->insertGetId([
            'school_id' => 1,
            'person_id' => $child2PersonId,
            'student_code' => '2026-0002',
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('aca_school_enrollments')->insert([
            ['school_id' => 1, 'year_id' => 1, 'student_id' => $student1Id, 'section_id' => 1, 'status' => 'activo', 'created_at' => now(), 'updated_at' => now()],
            ['school_id' => 1, 'year_id' => 1, 'student_id' => $student2Id, 'section_id' => 1, 'status' => 'activo', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('aca_school_student_guardians')->insert([
            ['school_id' => 1, 'student_id' => $student1Id, 'guardian_person_id' => $guardianPersonId, 'relationship' => 'padre', 'is_primary' => true, 'status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['school_id' => 1, 'student_id' => $student2Id, 'guardian_person_id' => $guardianPersonId, 'relationship' => 'padre', 'is_primary' => true, 'status' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $bot = new SchoolTelegramBotService();
        $registration = new SchoolTelegramRegistrationService();
        $controller = new SchoolTelegramWebhookController();
        $chatId = '99887766';

        // 1. El padre envía /registrar
        $this->sendWebhook($controller, $bot, $registration, $chatId, '/registrar');
        $this->assertStringContainsString('Paso 1', end($sentMessages)['text']);

        // 2. El padre escribe su DNI
        $this->sendWebhook($controller, $bot, $registration, $chatId, '40506070');
        $this->assertStringContainsString('Carlos Gómez', end($sentMessages)['text']);
        $this->assertStringContainsString('Paso 2', end($sentMessages)['text']);

        // 3. El padre escribe el DNI de su primer hijo
        $this->sendWebhook($controller, $bot, $registration, $chatId, '70809011');
        $this->assertStringContainsString('Mateo Gómez', end($sentMessages)['text']);
        $this->assertStringContainsString('¿Tienes otro hijo o hija', end($sentMessages)['text']);
        $this->assertSame(1, AcaSchoolTelegramSubscription::active()->count());

        // 4. El padre responde "Sí" porque tiene otro hijo
        $this->sendWebhook($controller, $bot, $registration, $chatId, 'Sí');
        $this->assertStringContainsString('DNI de tu otro hijo', end($sentMessages)['text']);

        // 5. El padre escribe el DNI de su segundo hijo
        $this->sendWebhook($controller, $bot, $registration, $chatId, '70809022');
        $this->assertStringContainsString('Lucía Gómez', end($sentMessages)['text']);
        $this->assertStringContainsString('¿Tienes otro hijo o hija', end($sentMessages)['text']);
        $this->assertSame(2, AcaSchoolTelegramSubscription::active()->count());

        // 6. El padre responde "No" para finalizar
        $this->sendWebhook($controller, $bot, $registration, $chatId, 'No');
        $this->assertStringContainsString('Mateo Gómez', end($sentMessages)['text']);
        $this->assertStringContainsString('Lucía Gómez', end($sentMessages)['text']);
        $this->assertNull($registration->sessionFor($chatId));

        // 7. Consulta /chatid e /info
        $this->sendWebhook($controller, $bot, $registration, $chatId, '/chatid');
        $this->assertStringContainsString($chatId, end($sentMessages)['text']);

        $this->sendWebhook($controller, $bot, $registration, $chatId, '/info');
        $this->assertStringContainsString('I.E. San Martín', end($sentMessages)['text']);
        $this->assertStringContainsString('Mateo Gómez', end($sentMessages)['text']);
        $this->assertStringContainsString('Lucía Gómez', end($sentMessages)['text']);

        // 8. El padre envía /baja y deja de recibir mensajes
        $this->sendWebhook($controller, $bot, $registration, $chatId, '/baja');
        $this->assertSame(0, AcaSchoolTelegramSubscription::active()->count());
    }

    public function test_encola_y_envia_aviso_de_entrada_y_salida_por_telegram(): void
    {
        Queue::fake();

        $ids = $this->createEnrolledStudent();
        $guardianPersonId = $this->addGuardian($ids['student_id'], 'Luis Pérez', '987654321', true);

        AcaSchoolTelegramSubscription::create([
            'school_id' => 1,
            'student_id' => $ids['student_id'],
            'guardian_person_id' => $guardianPersonId,
            'chat_id' => '55443322',
            'status' => 'active',
            'registered_at' => now(),
        ]);

        $gate = $this->createGate($ids['student_id'], $ids['enrollment_id']);
        $notices = new SchoolGateNoticeService(new SchoolTelegramNoticeService(new SchoolTelegramBotService()));

        $queued = $notices->notify($gate, SchoolGateNoticeService::EVENT_ENTRY);
        $this->assertSame(1, $queued);

        Queue::assertPushed(SendSchoolGateNotice::class, 1);

        $delivery = AcaSchoolNoticeDelivery::first();
        $this->assertSame('telegram', $delivery->channel);
        $this->assertSame('55443322', $delivery->chat_id);

        // Ejecutamos el Job y verificamos que envíe a la API de Telegram
        Http::fake(['*' => Http::response(['ok' => true, 'result' => ['message_id' => 10]], 200)]);

        (new SendSchoolGateNotice($delivery->id))->handle($notices);

        $delivery->refresh();
        $this->assertSame('sent', $delivery->status);
        $this->assertNotNull($delivery->sent_at);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['chat_id'] === '55443322'
            && str_contains($request['text'], 'Ana Pérez'));
    }

    private function sendWebhook(
        SchoolTelegramWebhookController $controller,
        SchoolTelegramBotService $bot,
        SchoolTelegramRegistrationService $registration,
        string $chatId,
        string $text
    ): void {
        $request = Request::create('/api/academic/school/telegram/webhook', 'POST', [
            'message' => [
                'chat' => ['id' => $chatId],
                'from' => ['username' => 'padre_tg', 'first_name' => 'Carlos'],
                'text' => $text,
            ],
        ]);
        $request->headers->set('X-Telegram-Bot-Api-Secret-Token', (string) $bot->secret());

        $response = $controller($request, $bot, $registration);
        $this->assertSame(200, $response->getStatusCode());
    }
}
