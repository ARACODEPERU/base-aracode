<?php

namespace Tests\Unit\Modules\Academic\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Academic\Services\SubscriptionContentPermissions;
use Tests\TestCase;

/**
 * Alcance y permisos de un tipo de suscripción.
 *
 * La promesa: cada plan declara qué categorías incluye (cursos/talleres,
 * Programas de Especialización y webinars) y qué contenido de especialización
 * ve (Meet/Zoom y Webinar). Nada depende del nombre del plan y los permisos de
 * varias suscripciones activas se combinan.
 */
class SubscriptionContentPermissionsTest extends TestCase
{
    private const TABLES = [
        'aca_student_subscriptions',
        'aca_subscription_types',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropSchema();
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        $this->dropSchema();

        parent::tearDown();
    }

    public function test_sin_suscripciones_no_otorga_ningun_permiso(): void
    {
        $permissions = SubscriptionContentPermissions::forStudent(1);

        $this->assertFalse($permissions['access_all_courses']);
        $this->assertFalse($permissions['access_specialization_programs']);
        $this->assertFalse($permissions['access_all_webinars']);
        $this->assertFalse($permissions['can_view_specialization_zooms']);
        $this->assertFalse($permissions['can_view_specialization_webinars']);
        $this->assertFalse(SubscriptionContentPermissions::canAccessCourse($permissions, 'Cursos Taller'));
    }

    public function test_un_plan_de_cursos_no_da_acceso_a_especializacion_ni_webinars(): void
    {
        $this->subscriptionType(1, 'Plan Cursos', ['access_all_courses' => true]);
        $this->subscription(1, 1);

        $permissions = SubscriptionContentPermissions::forStudent(1);

        $this->assertTrue($permissions['access_all_courses']);
        $this->assertFalse($permissions['access_specialization_programs']);
        $this->assertFalse($permissions['access_all_webinars']);
        $this->assertTrue(SubscriptionContentPermissions::canAccessCourse($permissions, 'Cursos Taller'));
        $this->assertFalse(SubscriptionContentPermissions::canAccessCourse($permissions, 'Programas de Especialización'));
        $this->assertFalse(SubscriptionContentPermissions::canAccessCourse($permissions, 'Webinar'));
    }

    public function test_un_plan_de_especializacion_solo_da_acceso_a_esos_programas(): void
    {
        $this->subscriptionType(1, 'Plan Especialización', [
            'access_specialization_programs' => true,
            'can_view_specialization_zooms' => true,
            'can_view_specialization_webinars' => true,
        ]);
        $this->subscription(1, 1);

        $permissions = SubscriptionContentPermissions::forStudent(1);

        $this->assertTrue(SubscriptionContentPermissions::includesSpecializationPrograms($permissions));
        $this->assertTrue(SubscriptionContentPermissions::canAccessCourse($permissions, 'Programas de Especialización'));
        $this->assertFalse(SubscriptionContentPermissions::canAccessCourse($permissions, 'Cursos Taller'));
        $this->assertTrue($permissions['can_view_specialization_zooms']);
        $this->assertTrue($permissions['can_view_specialization_webinars']);
    }

    public function test_un_plan_de_webinars_da_acceso_a_los_cursos_de_tipo_webinar(): void
    {
        $this->subscriptionType(1, 'Plan Webinars', ['access_all_webinars' => true]);
        $this->subscription(1, 1);

        $permissions = SubscriptionContentPermissions::forStudent(1);

        $this->assertTrue(SubscriptionContentPermissions::canAccessCourse($permissions, 'Webinar'));
        $this->assertFalse(SubscriptionContentPermissions::canAccessCourse($permissions, 'Cursos Taller'));
    }

    public function test_un_tipo_de_curso_desconocido_cae_en_acceso_a_cursos(): void
    {
        $this->subscriptionType(1, 'Plan Cursos', ['access_all_courses' => true]);
        $this->subscription(1, 1);

        $permissions = SubscriptionContentPermissions::forStudent(1);

        $this->assertTrue(SubscriptionContentPermissions::canAccessCourse($permissions, 'Bootcamp'));
        $this->assertTrue(SubscriptionContentPermissions::canAccessCourse($permissions, null));
    }

    public function test_una_suscripcion_inactiva_no_otorga_permisos(): void
    {
        $this->subscriptionType(1, 'Plan Cursos', ['access_all_courses' => true, 'access_all_webinars' => true]);
        $this->subscription(1, 1, status: false);

        $permissions = SubscriptionContentPermissions::forStudent(1);

        $this->assertFalse($permissions['access_all_courses']);
        $this->assertFalse($permissions['access_all_webinars']);
    }

    public function test_una_suscripcion_con_estado_activo_pero_vencida_no_otorga_permisos(): void
    {
        // El comando de vencimiento no corrió todavía: las fechas mandan.
        $this->subscriptionType(1, 'Plan Cursos', ['access_all_courses' => true]);
        $this->subscription(1, 1, true, now()->subMonths(2)->toDateString(), now()->subMonth()->toDateString());

        $this->assertFalse(SubscriptionContentPermissions::forStudent(1)['access_all_courses']);
    }

    public function test_una_suscripcion_vigente_por_fechas_otorga_permisos_con_el_estado_apagado(): void
    {
        $this->subscriptionType(1, 'Plan Cursos', ['access_all_courses' => true]);
        $this->subscription(1, 1, false, now()->subWeek()->toDateString(), now()->addWeek()->toDateString());

        $this->assertTrue(SubscriptionContentPermissions::forStudent(1)['access_all_courses']);
    }

    public function test_los_permisos_de_varias_suscripciones_activas_se_combinan(): void
    {
        $this->subscriptionType(1, 'Plan Cursos', ['access_all_courses' => true]);
        $this->subscriptionType(2, 'Plan Webinars', ['access_all_webinars' => true]);
        $this->subscription(1, 1);
        $this->subscription(1, 2);

        $permissions = SubscriptionContentPermissions::forStudent(1);

        $this->assertTrue($permissions['access_all_courses']);
        $this->assertTrue($permissions['access_all_webinars']);
    }

    public function test_el_nombre_del_plan_no_otorga_ningun_permiso(): void
    {
        // Aunque se llame "Premium VIP", sin flags no incluye nada.
        $this->subscriptionType(1, 'Premium VIP');
        $this->subscription(1, 1);

        $permissions = SubscriptionContentPermissions::forStudent(1);

        $this->assertFalse(SubscriptionContentPermissions::includesSpecializationPrograms($permissions));
        $this->assertFalse(SubscriptionContentPermissions::canAccessCourse($permissions, 'Programas de Especialización'));
        $this->assertFalse(SubscriptionContentPermissions::canAccessCourse($permissions, 'Cursos Taller'));
    }

    public function test_los_permisos_no_se_filtran_entre_alumnos(): void
    {
        $this->subscriptionType(1, 'Plan Cursos', ['access_all_courses' => true]);
        $this->subscription(1, 1);

        $this->assertTrue(SubscriptionContentPermissions::forStudent(1)['access_all_courses']);
        $this->assertFalse(SubscriptionContentPermissions::forStudent(2)['access_all_courses']);
    }

    /**
     * @param array<string, bool> $flags
     */
    private function subscriptionType(int $id, string $title, array $flags = []): void
    {
        DB::table('aca_subscription_types')->insert([
            'id' => $id,
            'title' => $title,
            'access_all_courses' => $flags['access_all_courses'] ?? false,
            'access_specialization_programs' => $flags['access_specialization_programs'] ?? false,
            'access_all_webinars' => $flags['access_all_webinars'] ?? false,
            'can_view_specialization_zooms' => $flags['can_view_specialization_zooms'] ?? false,
            'can_view_specialization_webinars' => $flags['can_view_specialization_webinars'] ?? false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function subscription(
        int $studentId,
        int $subscriptionTypeId,
        bool $status = true,
        ?string $dateStart = null,
        ?string $dateEnd = null
    ): void {
        DB::table('aca_student_subscriptions')->insert([
            'student_id' => $studentId,
            'subscription_id' => $subscriptionTypeId,
            'status' => $status,
            'date_start' => $dateStart,
            'date_end' => $dateEnd,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('aca_subscription_types', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->boolean('access_all_courses')->default(false);
            $table->boolean('access_specialization_programs')->default(false);
            $table->boolean('access_all_webinars')->default(false);
            $table->boolean('can_view_specialization_zooms')->default(false);
            $table->boolean('can_view_specialization_webinars')->default(false);
            $table->timestamps();
        });

        Schema::create('aca_student_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    private function dropSchema(): void
    {
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
}
