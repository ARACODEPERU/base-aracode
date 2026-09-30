<?php

namespace Tests\Unit\Modules\Academic\Services;

use App\Services\JobOffersAccess;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Services\NotificationAudienceResolver;
use Modules\Academic\Services\TelegramStudentDirectory;
use Tests\TestCase;
use Tests\Unit\Modules\Academic\Concerns\BuildsTelegramStudentSchema;

/**
 * Padrón que valida el documento escrito en el chat del bot.
 *
 * La promesa: solo entra quien tiene matrícula activa en un programa de
 * especialización o una suscripción vigente; el documento se compara sin
 * distinguir mayúsculas; y se distingue "documento desconocido" (null) de
 * "persona sin acceso" (ficha con programas vacío), para que el bot pueda
 * responder distinto sin exponer datos de terceros.
 */
class TelegramStudentDirectoryTest extends TestCase
{
    use BuildsTelegramStudentSchema;

    private TelegramStudentDirectory $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropTelegramStudentSchema();
        $this->createTelegramStudentSchema();

        $this->directory = new TelegramStudentDirectory(new NotificationAudienceResolver());
    }

    protected function tearDown(): void
    {
        $this->dropTelegramStudentSchema();

        parent::tearDown();
    }

    public function test_un_documento_desconocido_no_resuelve_a_nadie(): void
    {
        $this->assertNull($this->directory->resolveByDocument('99999999'));
        $this->assertNull($this->directory->resolveByDocument('   '));
    }

    public function test_la_matricula_activa_en_un_programa_de_especializacion_registra(): void
    {
        $studentId = $this->student('12345678', 'Ana Pérez');
        $this->course('Especialización en NIIF', JobOffersAccess::SPECIALIZATION_TYPE);
        $this->enroll($studentId, 1);

        $result = $this->directory->resolveByDocument('12345678');

        $this->assertNotNull($result);
        $this->assertSame(1, $result['person_id']);
        $this->assertSame('Ana Pérez', $result['name']);
        $this->assertSame(['Especialización en NIIF'], $result['programs']);
        $this->assertFalse($result['subscription']);
    }

    public function test_una_matricula_inactiva_o_de_otro_tipo_de_curso_no_cuenta(): void
    {
        $studentId = $this->student('12345678', 'Ana');
        $this->course('Curso suelto', 'Cursos');
        $this->enroll($studentId, 1, true);

        $this->course('Especialización suspendida', JobOffersAccess::SPECIALIZATION_TYPE);
        $this->enroll($studentId, 2, false);

        $result = $this->directory->resolveByDocument('12345678');

        $this->assertSame([], $result['programs']);
        $this->assertFalse($result['subscription']);
    }

    public function test_una_suscripcion_vigente_por_fechas_basta(): void
    {
        $studentId = $this->student('12345678', 'Luis');
        $this->subscription($studentId, [
            'status' => false,
            'date_start' => now()->subMonth()->toDateString(),
            'date_end' => now()->addMonth()->toDateString(),
        ]);

        $result = $this->directory->resolveByDocument('12345678');

        $this->assertSame([], $result['programs']);
        $this->assertTrue($result['subscription']);
    }

    public function test_una_suscripcion_vencida_no_basta(): void
    {
        $studentId = $this->student('12345678', 'Luis');
        $this->subscription($studentId, [
            'status' => false,
            'date_start' => now()->subMonths(3)->toDateString(),
            'date_end' => now()->subMonth()->toDateString(),
        ]);

        $result = $this->directory->resolveByDocument('12345678');

        $this->assertFalse($result['subscription']);
    }

    public function test_el_estado_activo_de_la_suscripcion_tambien_basta(): void
    {
        $studentId = $this->student('12345678', 'Luis');
        $this->subscription($studentId, ['status' => true]);

        $this->assertTrue($this->directory->resolveByDocument('12345678')['subscription']);
    }

    public function test_una_persona_que_no_es_alumno_existe_pero_sin_acceso(): void
    {
        DB::table('people')->insert([
            'short_name' => 'Proveedor',
            'full_name' => 'Proveedor SAC',
            'number' => '20123456789',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->directory->resolveByDocument('20123456789');

        $this->assertNotNull($result);
        $this->assertSame('Proveedor', $result['name']);
        $this->assertSame([], $result['programs']);
        $this->assertFalse($result['subscription']);
    }

    public function test_el_documento_se_compara_sin_distinguir_mayusculas(): void
    {
        $studentId = $this->student('abc123', 'Extranjero');
        $this->course('Especialización internacional', JobOffersAccess::SPECIALIZATION_TYPE);
        $this->enroll($studentId, 1);

        $result = $this->directory->resolveByDocument('ABC123');

        $this->assertNotNull($result);
        $this->assertSame(['Especialización internacional'], $result['programs']);
    }

    public function test_el_nombre_cae_al_nombre_completo_cuando_falta_el_corto(): void
    {
        $studentId = $this->student('12345678', null, 'Ana Pérez Quispe');
        $this->course('Especialización', JobOffersAccess::SPECIALIZATION_TYPE);
        $this->enroll($studentId, 1);

        $this->assertSame('Ana Pérez Quispe', $this->directory->resolveByDocument('12345678')['name']);
    }

    public function test_los_programas_no_se_repiten(): void
    {
        $studentId = $this->student('12345678', 'Ana');
        $this->course('Especialización en NIIF', JobOffersAccess::SPECIALIZATION_TYPE);
        $this->enroll($studentId, 1);
        $this->enroll($studentId, 1);

        $result = $this->directory->resolveByDocument('12345678');

        $this->assertSame(['Especialización en NIIF'], $result['programs']);
    }

    /**
     * Crea una persona con su alumno y devuelve el id del alumno.
     */
    private function student(string $document, ?string $shortName, ?string $fullName = null): int
    {
        $personId = DB::table('people')->insertGetId([
            'short_name' => $shortName,
            'full_name' => $fullName ?? $shortName,
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

    private function enroll(int $studentId, int $courseId, bool $status = true): void
    {
        DB::table('aca_cap_registrations')->insert([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param array{status?: bool, date_start?: string, date_end?: string} $attributes
     */
    private function subscription(int $studentId, array $attributes = []): void
    {
        DB::table('aca_student_subscriptions')->insert([
            'student_id' => $studentId,
            'subscription_id' => 1,
            'status' => $attributes['status'] ?? false,
            'date_start' => $attributes['date_start'] ?? null,
            'date_end' => $attributes['date_end'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
