<?php

namespace Tests\Unit\Modules\Academic\Jobs;

use Illuminate\Support\Facades\Http;
use Modules\Academic\Entities\AcaSchoolNoticeDelivery;
use Modules\Academic\Jobs\SendSchoolGateNotice;
use Modules\Academic\Services\SchoolGateNoticeService;
use Tests\TestCase;
use Tests\Unit\Modules\Academic\Concerns\BuildsSchoolNoticeSchema;

/**
 * Contrato del envío del aviso de asistencia por SMSGate.
 *
 * La promesa: el job manda el SMS al API externo de SMSGate con el texto ya
 * armado, marca la entrega como enviada y, si el servidor la rechaza de forma
 * permanente (4xx) o el canal se apagó, deja el motivo sin reventar la cola.
 * Nada sale a la red: SMSGate se simula con Http::fake.
 */
class SendSchoolGateNoticeJobTest extends TestCase
{
    use BuildsSchoolNoticeSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropSchoolNoticeSchema();
        $this->createSchoolNoticeSchema();
    }

    protected function tearDown(): void
    {
        $this->dropSchoolNoticeSchema();

        parent::tearDown();
    }

    public function test_el_job_envia_el_sms_y_marca_la_entrega_como_enviada(): void
    {
        Http::fake(['*' => Http::response(['id' => 'abc', 'state' => 'Pending'], 200)]);
        $this->enableSchoolNoticeChannel();

        $deliveryId = $this->pendingDelivery();

        (new SendSchoolGateNotice($deliveryId))->handle(new SchoolGateNoticeService());

        $delivery = AcaSchoolNoticeDelivery::find($deliveryId);
        $this->assertSame('sent', $delivery->status);
        $this->assertNotNull($delivery->sent_at);
        $this->assertNull($delivery->error_message);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/3rdparty/v1/messages')
            && $request['phoneNumbers'][0] === '+51987654321'
            && str_contains($request['textMessage']['text'], 'Ana Pérez'));
    }

    public function test_un_rechazo_permanente_de_smsgate_queda_como_fallido(): void
    {
        Http::fake(['*' => Http::response('invalid phone number', 400)]);
        $this->enableSchoolNoticeChannel();

        $deliveryId = $this->pendingDelivery();

        // No debe lanzar: un rechazo permanente no se reintenta.
        (new SendSchoolGateNotice($deliveryId))->handle(new SchoolGateNoticeService());

        $delivery = AcaSchoolNoticeDelivery::find($deliveryId);
        $this->assertSame('failed', $delivery->status);
        $this->assertStringContainsString('SMSGate', (string) $delivery->error_message);
    }

    public function test_el_canal_apagado_omite_el_aviso(): void
    {
        Http::fake();
        $this->enableSchoolNoticeChannel();
        $this->setChannelParameter('SC-00023', '0');

        $deliveryId = $this->pendingDelivery();

        (new SendSchoolGateNotice($deliveryId))->handle(new SchoolGateNoticeService());

        $delivery = AcaSchoolNoticeDelivery::find($deliveryId);
        $this->assertSame('skipped', $delivery->status);
        Http::assertNothingSent();
    }

    public function test_una_entrega_ya_enviada_no_se_repite(): void
    {
        Http::fake();
        $this->enableSchoolNoticeChannel();

        $deliveryId = $this->pendingDelivery();
        AcaSchoolNoticeDelivery::where('id', $deliveryId)->update(['status' => 'sent']);

        (new SendSchoolGateNotice($deliveryId))->handle(new SchoolGateNoticeService());

        Http::assertNothingSent();
        $this->assertSame('sent', AcaSchoolNoticeDelivery::find($deliveryId)->status);
    }

    /**
     * Crea la asistencia, su apoderado y una entrega pendiente. Devuelve el id
     * de la entrega.
     */
    private function pendingDelivery(): int
    {
        $ids = $this->createEnrolledStudent();
        $guardianPersonId = $this->addGuardian($ids['student_id'], 'Luis Pérez', '987654321', true);
        $gate = $this->createGate($ids['student_id'], $ids['enrollment_id']);

        return (int) AcaSchoolNoticeDelivery::create([
            'gate_attendance_id' => $gate->id,
            'student_id' => $ids['student_id'],
            'guardian_person_id' => $guardianPersonId,
            'recipient_key' => 'g1',
            'event' => 'in',
            'phone' => '51987654321',
            'status' => 'pending',
        ])->id;
    }
}
