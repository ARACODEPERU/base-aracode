<?php

namespace Tests\Unit\Modules\Academic\Services;

use Illuminate\Support\Facades\Queue;
use Modules\Academic\Entities\AcaSchoolGateAttendance;
use Modules\Academic\Entities\AcaSchoolNoticeDelivery;
use Modules\Academic\Jobs\SendSchoolGateNotice;
use Modules\Academic\Services\SchoolGateNoticeService;
use Tests\TestCase;
use Tests\Unit\Modules\Academic\Concerns\BuildsSchoolNoticeSchema;

/**
 * Contrato del canal de avisos de asistencia al apoderado.
 *
 * La promesa: el aviso solo sale cuando el canal está configurado y encendido,
 * el evento (entrada, salida, tardanza o salida anticipada) está habilitado y
 * hay un teléfono utilizable; se envía a cada apoderado con teléfono (o al
 * contacto de la matrícula), sin duplicar el mismo evento y respetando el
 * límite diario. La cola se simula con Queue::fake: la prueba mira lo encolado,
 * no el envío real.
 */
class SchoolGateNoticeServiceTest extends TestCase
{
    use BuildsSchoolNoticeSchema;

    private SchoolGateNoticeService $notices;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->dropSchoolNoticeSchema();
        $this->createSchoolNoticeSchema();

        $this->notices = new SchoolGateNoticeService();
    }

    protected function tearDown(): void
    {
        $this->dropSchoolNoticeSchema();

        parent::tearDown();
    }

    public function test_el_canal_apagado_no_encola_avisos(): void
    {
        $this->enableSchoolNoticeChannel();
        $this->setChannelParameter('SC-00023', '0');

        $ids = $this->createEnrolledStudent();
        $this->addGuardian($ids['student_id'], 'Luis Pérez', '987654321', true);
        $gate = $this->createGate($ids['student_id'], $ids['enrollment_id']);

        $this->assertSame(0, $this->notices->notify($gate, SchoolGateNoticeService::EVENT_ENTRY));
        Queue::assertNothingPushed();
        $this->assertSame(0, AcaSchoolNoticeDelivery::count());
    }

    public function test_sin_credenciales_no_encola_avisos(): void
    {
        // Canal encendido pero sin URL, usuario ni contraseña.
        $this->setChannelParameter('SC-00023', '1');

        $ids = $this->createEnrolledStudent();
        $this->addGuardian($ids['student_id'], 'Luis Pérez', '987654321', true);
        $gate = $this->createGate($ids['student_id'], $ids['enrollment_id']);

        $this->assertFalse($this->notices->isConfigured());
        $this->assertSame(0, $this->notices->notify($gate, SchoolGateNoticeService::EVENT_ENTRY));
        Queue::assertNothingPushed();
    }

    public function test_la_entrada_encola_un_aviso_por_apoderado_con_telefono(): void
    {
        $this->enableSchoolNoticeChannel();

        $ids = $this->createEnrolledStudent();
        $this->addGuardian($ids['student_id'], 'Luis Pérez', '987654321', true);
        $this->addGuardian($ids['student_id'], 'Rosa Díaz', '987111222');
        $gate = $this->createGate($ids['student_id'], $ids['enrollment_id']);

        $this->assertSame(2, $this->notices->notify($gate, SchoolGateNoticeService::EVENT_ENTRY));

        Queue::assertPushed(SendSchoolGateNotice::class, 2);
        $this->assertSame(2, AcaSchoolNoticeDelivery::count());

        // El teléfono se guarda normalizado a E.164 (sin el «+»).
        $this->assertDatabaseHas('aca_school_notice_deliveries', [
            'phone' => '51987654321',
            'event' => 'in',
            'status' => 'pending',
        ]);
    }

    public function test_el_mismo_evento_y_destinatario_no_se_duplica(): void
    {
        $this->enableSchoolNoticeChannel();

        $ids = $this->createEnrolledStudent();
        $this->addGuardian($ids['student_id'], 'Luis Pérez', '987654321', true);
        $gate = $this->createGate($ids['student_id'], $ids['enrollment_id']);

        $this->assertSame(1, $this->notices->notify($gate, SchoolGateNoticeService::EVENT_ENTRY));
        $this->assertSame(0, $this->notices->notify($gate, SchoolGateNoticeService::EVENT_ENTRY));

        $this->assertSame(1, AcaSchoolNoticeDelivery::count());
        Queue::assertPushed(SendSchoolGateNotice::class, 1);
    }

    public function test_la_tardanza_no_avisa_cuando_el_interruptor_esta_apagado(): void
    {
        $this->enableSchoolNoticeChannel();
        $this->setChannelParameter('SC-00026', '0');

        $ids = $this->createEnrolledStudent();
        $this->addGuardian($ids['student_id'], 'Luis Pérez', '987654321', true);
        $gate = $this->createGate($ids['student_id'], $ids['enrollment_id'], [
            'entry_status' => AcaSchoolGateAttendance::ENTRY_TARDANZA,
        ]);

        $this->assertSame(0, $this->notices->notify($gate, SchoolGateNoticeService::EVENT_ENTRY));
        Queue::assertNothingPushed();
    }

    public function test_la_tardanza_avisa_cuando_el_interruptor_esta_encendido(): void
    {
        $this->enableSchoolNoticeChannel();

        $ids = $this->createEnrolledStudent();
        $this->addGuardian($ids['student_id'], 'Luis Pérez', '987654321', true);
        $gate = $this->createGate($ids['student_id'], $ids['enrollment_id'], [
            'entry_status' => AcaSchoolGateAttendance::ENTRY_TARDANZA,
        ]);

        $this->assertSame(1, $this->notices->notify($gate, SchoolGateNoticeService::EVENT_ENTRY));

        $variables = $this->notices->noticeVariables($gate, SchoolGateNoticeService::EVENT_ENTRY, 'Luis Pérez');
        $this->assertSame('Tardanza', $variables['estado']);
    }

    public function test_la_salida_anticipada_no_avisa_cuando_el_interruptor_esta_apagado(): void
    {
        $this->enableSchoolNoticeChannel();
        $this->setChannelParameter('SC-00027', '0');

        $ids = $this->createEnrolledStudent();
        $this->addGuardian($ids['student_id'], 'Luis Pérez', '987654321', true);
        $gate = $this->createGate($ids['student_id'], $ids['enrollment_id'], [
            'exit_at' => now(),
            'early_exit' => true,
            'last_event' => AcaSchoolGateAttendance::EVENT_SALIDA,
        ]);

        $this->assertSame(0, $this->notices->notify($gate, SchoolGateNoticeService::EVENT_EXIT));
        Queue::assertNothingPushed();
    }

    public function test_solo_al_apoderado_principal_cuando_el_interruptor_esta_encendido(): void
    {
        $this->enableSchoolNoticeChannel();
        $this->setChannelParameter('SC-00030', '1');

        $ids = $this->createEnrolledStudent();
        $this->addGuardian($ids['student_id'], 'Luis Pérez', '987654321', true);
        $this->addGuardian($ids['student_id'], 'Rosa Díaz', '987111222');
        $gate = $this->createGate($ids['student_id'], $ids['enrollment_id']);

        $this->assertSame(1, $this->notices->notify($gate, SchoolGateNoticeService::EVENT_ENTRY));
        $this->assertDatabaseHas('aca_school_notice_deliveries', ['phone' => '51987654321']);
    }

    public function test_sin_apoderados_usa_el_telefono_de_la_matricula(): void
    {
        $this->enableSchoolNoticeChannel();

        $ids = $this->createEnrolledStudent('987333444');
        $gate = $this->createGate($ids['student_id'], $ids['enrollment_id']);

        $this->assertSame(1, $this->notices->notify($gate, SchoolGateNoticeService::EVENT_ENTRY));
        $this->assertDatabaseHas('aca_school_notice_deliveries', [
            'phone' => '51987333444',
            'recipient_key' => 'e' . $ids['enrollment_id'],
        ]);
    }

    public function test_un_apoderado_inactivo_o_sin_telefono_no_recibe_aviso(): void
    {
        $this->enableSchoolNoticeChannel();

        $ids = $this->createEnrolledStudent();
        $this->addGuardian($ids['student_id'], 'Apoderado inactivo', '987654321', true, false);
        $this->addGuardian($ids['student_id'], 'Sin teléfono', null);
        $gate = $this->createGate($ids['student_id'], $ids['enrollment_id']);

        $this->assertSame(0, $this->notices->notify($gate, SchoolGateNoticeService::EVENT_ENTRY));
        Queue::assertNothingPushed();
    }

    public function test_el_limite_diario_frena_los_avisos(): void
    {
        $this->enableSchoolNoticeChannel();
        $this->setChannelParameter('SC-00031', '1');

        $ids = $this->createEnrolledStudent();
        $this->addGuardian($ids['student_id'], 'Luis Pérez', '987654321', true);
        $this->addGuardian($ids['student_id'], 'Rosa Díaz', '987111222');
        $gate = $this->createGate($ids['student_id'], $ids['enrollment_id']);

        $this->assertSame(1, $this->notices->notify($gate, SchoolGateNoticeService::EVENT_ENTRY));
        $this->assertSame(1, AcaSchoolNoticeDelivery::count());
    }

    public function test_las_variables_de_la_plantilla_se_reemplazan(): void
    {
        $this->enableSchoolNoticeChannel();
        $this->setChannelParameter('SC-00028', 'Hola {apoderado}: {alumno} ingresó a las {hora} ({estado}).');

        $ids = $this->createEnrolledStudent();
        $this->addGuardian($ids['student_id'], 'Luis Pérez', '987654321', true);
        $gate = $this->createGate($ids['student_id'], $ids['enrollment_id']);

        $variables = $this->notices->noticeVariables($gate, SchoolGateNoticeService::EVENT_ENTRY, 'Luis Pérez');
        $text = $this->notices->renderNoticeText(SchoolGateNoticeService::EVENT_ENTRY, $variables);

        $this->assertStringContainsString('Hola Luis Pérez', $text);
        $this->assertStringContainsString('Ana Pérez', $text);
        $this->assertStringContainsString('Asistencia', $text);
        $this->assertStringContainsString($variables['hora'], $text);
    }

    public function test_sin_plantilla_guardada_usa_el_texto_por_defecto(): void
    {
        $this->enableSchoolNoticeChannel();

        $ids = $this->createEnrolledStudent();
        $this->addGuardian($ids['student_id'], 'Luis Pérez', '987654321', true);
        $gate = $this->createGate($ids['student_id'], $ids['enrollment_id']);

        $variables = $this->notices->noticeVariables($gate, SchoolGateNoticeService::EVENT_ENTRY, 'Luis Pérez');
        $text = $this->notices->renderNoticeText(SchoolGateNoticeService::EVENT_ENTRY, $variables);

        $this->assertStringContainsString('INGRESO', $text);
        $this->assertStringContainsString('I.E. San Martín', $text);
    }
}
