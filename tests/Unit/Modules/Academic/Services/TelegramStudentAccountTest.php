<?php

namespace Tests\Unit\Modules\Academic\Services;

use Illuminate\Support\Facades\DB;
use Modules\Academic\Services\TelegramStudentAccount;
use Tests\TestCase;
use Tests\Unit\Modules\Academic\Concerns\BuildsTelegramStudentSchema;

/**
 * Padrón de consulta del bot de Telegram (correo + documento).
 *
 * La promesa: solo responde cuando el correo pertenece a la persona dueña del
 * documento; los cursos son los de pago vigentes con matrícula activa; la
 * suscripción activa se informa y se marca si es Premium VIP; y los certificados
 * salen con su enlace de descarga.
 */
class TelegramStudentAccountTest extends TestCase
{
    use BuildsTelegramStudentSchema;

    private TelegramStudentAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropTelegramStudentSchema();
        $this->createTelegramStudentSchema();

        $this->account = new TelegramStudentAccount();
    }

    protected function tearDown(): void
    {
        $this->dropTelegramStudentSchema();

        parent::tearDown();
    }

    public function test_un_documento_desconocido_no_resuelve_a_nadie(): void
    {
        $this->assertNull($this->account->resolveAccount('99999999', 'alguien@correo.com'));
    }

    public function test_un_correo_que_no_pertenece_a_la_persona_no_resuelve(): void
    {
        $this->student('12345678', 'Ana', 'ana@correo.com');

        $this->assertNull($this->account->resolveAccount('12345678', 'otro@correo.com'));
    }

    public function test_el_correo_se_compara_sin_distinguir_mayusculas(): void
    {
        $this->student('12345678', 'Ana', 'Ana@Correo.com');

        $result = $this->account->resolveAccount('12345678', '  ana@correo.com ');

        $this->assertNotNull($result);
        $this->assertSame('Ana', $result['name']);
    }

    public function test_solo_lista_los_cursos_de_pago_vigentes_con_matricula_activa(): void
    {
        $studentId = $this->student('12345678', 'Ana', 'ana@correo.com');

        $this->course('Curso ilimitado', 'Cursos', 150, 1);
        $this->enroll($studentId, 1, true, true);

        $this->course('Curso vigente', 'Cursos', 120, 2);
        $this->enroll($studentId, 2, true, false, now()->addMonth()->toDateString());

        $this->course('Curso gratis', 'Cursos', 0, 3);
        $this->enroll($studentId, 3, true, true);

        $this->course('Matrícula inactiva', 'Cursos', 100, 4);
        $this->enroll($studentId, 4, false, true);

        $this->course('Curso vencido', 'Cursos', 100, 5);
        $this->enroll($studentId, 5, true, false, now()->subMonth()->toDateString());

        $result = $this->account->resolveAccount('12345678', 'ana@correo.com');

        $descriptions = array_column($result['courses'], 'description');

        $this->assertSame(['Curso ilimitado', 'Curso vigente'], $descriptions);
        $this->assertSame('Acceso ilimitado', $result['courses'][0]['time_limit']);
        $this->assertStringContainsString('Vigente hasta', $result['courses'][1]['time_limit']);
    }

    public function test_una_suscripcion_vigente_se_informa_sin_premium(): void
    {
        $studentId = $this->student('12345678', 'Ana', 'ana@correo.com');
        $this->subscriptionType(1, 'Plan Mensual');
        $this->subscription($studentId, 1, ['status' => true]);

        $result = $this->account->resolveAccount('12345678', 'ana@correo.com');

        $this->assertNotNull($result['subscription']);
        $this->assertFalse($result['subscription']['vip']);
    }

    public function test_una_suscripcion_premium_vip_se_marca_como_tal(): void
    {
        $studentId = $this->student('12345678', 'Ana', 'ana@correo.com');
        $this->subscriptionType(1, 'Suscripción Premium VIP');
        $this->subscription($studentId, 1, ['status' => true]);

        $result = $this->account->resolveAccount('12345678', 'ana@correo.com');

        $this->assertTrue($result['subscription']['vip']);
    }

    public function test_una_suscripcion_vencida_no_cuenta(): void
    {
        $studentId = $this->student('12345678', 'Ana', 'ana@correo.com');
        $this->subscriptionType(1, 'Plan Mensual');
        $this->subscription($studentId, 1, [
            'status' => false,
            'date_start' => now()->subMonths(3)->toDateString(),
            'date_end' => now()->subMonth()->toDateString(),
        ]);

        $this->assertNull($this->account->resolveAccount('12345678', 'ana@correo.com')['subscription']);
    }

    public function test_los_certificados_salen_con_su_enlace_de_descarga(): void
    {
        $studentId = $this->student('12345678', 'Ana', 'ana@correo.com');
        $courseId = $this->course('Especialización', 'Programas de Especialización', 200, 1);
        $this->certificate($studentId, $courseId);

        $moduleId = $this->module($courseId, 'Módulo introductorio');
        $this->certificate($studentId, $courseId, $moduleId);

        $result = $this->account->resolveAccount('12345678', 'ana@correo.com');

        $this->assertCount(2, $result['certificates']);
        $this->assertSame('Especialización', $result['certificates'][0]['course']);
        $this->assertNull($result['certificates'][0]['module']);
        $this->assertSame('Módulo introductorio', $result['certificates'][1]['module']);
        $this->assertStringContainsString(
            'academic/certificate/image/' . $result['certificates'][0]['id'] . '/download',
            $result['certificates'][0]['url']
        );
    }

    public function test_una_persona_que_no_es_alumno_existe_pero_sin_datos(): void
    {
        DB::table('people')->insert([
            'short_name' => 'Proveedor',
            'full_name' => 'Proveedor SAC',
            'number' => '20123456789',
            'email' => 'proveedor@correo.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->account->resolveAccount('20123456789', 'proveedor@correo.com');

        $this->assertNotNull($result);
        $this->assertSame([], $result['courses']);
        $this->assertNull($result['subscription']);
        $this->assertSame([], $result['certificates']);
    }

    private function student(string $document, string $name, ?string $email = null): int
    {
        $personId = DB::table('people')->insertGetId([
            'short_name' => $name,
            'full_name' => $name,
            'number' => $document,
            'email' => $email,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('aca_students')->insertGetId([
            'person_id' => $personId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function course(string $description, string $type, float $price, int $id = 0): int
    {
        $row = [
            'description' => $description,
            'type_description' => $type,
            'price' => $price,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($id > 0) {
            DB::table('aca_courses')->insert($row + ['id' => $id]);

            return $id;
        }

        return (int) DB::table('aca_courses')->insertGetId($row);
    }

    private function enroll(
        int $studentId,
        int $courseId,
        bool $status = true,
        bool $unlimited = true,
        ?string $dateEnd = null
    ): void {
        DB::table('aca_cap_registrations')->insert([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'status' => $status,
            'unlimited' => $unlimited,
            'date_end' => $dateEnd,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function subscriptionType(int $id, string $title): void
    {
        DB::table('aca_subscription_types')->insert([
            'id' => $id,
            'title' => $title,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param array{status?: bool, date_start?: string, date_end?: string} $attributes
     */
    private function subscription(int $studentId, int $subscriptionId, array $attributes = []): void
    {
        DB::table('aca_student_subscriptions')->insert([
            'student_id' => $studentId,
            'subscription_id' => $subscriptionId,
            'status' => $attributes['status'] ?? false,
            'date_start' => $attributes['date_start'] ?? null,
            'date_end' => $attributes['date_end'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function module(int $courseId, string $description): int
    {
        return (int) DB::table('aca_modules')->insertGetId([
            'course_id' => $courseId,
            'description' => $description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function certificate(int $studentId, int $courseId, ?int $moduleId = null): int
    {
        return (int) DB::table('aca_certificates')->insertGetId([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'module_id' => $moduleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
