<?php

namespace Tests\Feature\Academic;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Academic\Emails\SubscriptionChargeFailed;
use Modules\Academic\Emails\SubscriptionGraceExpired;
use Modules\Academic\Emails\SubscriptionRenewed;
use Modules\Academic\Entities\AcaSubscriptionPayment;
use Modules\Academic\Entities\AcaSubscriptionPreapproval;
use Modules\Academic\Services\SubscriptionRecurringNotifier;
use Tests\TestCase;

/**
 * Correos de los cobros recurrentes: renovación, primer fallo y fin de gracia.
 */
class SubscriptionRecurringNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config(['services.mercadopago.subscription_grace_days' => 3]);
    }

    public function test_avisa_la_renovacion_una_sola_vez(): void
    {
        $preapproval = $this->preapproval();
        $pago = $this->pago();

        $notifier = app(SubscriptionRecurringNotifier::class);

        $this->assertTrue($notifier->chargeApproved($preapproval, $pago));
        $this->assertFalse($notifier->chargeApproved($preapproval->fresh(), $pago->fresh()));

        Mail::assertQueued(SubscriptionRenewed::class, 1);
        Mail::assertQueued(SubscriptionRenewed::class, fn ($mail) => $mail->hasTo('alumno@example.com')
            && str_contains($mail->render(), 'se renovó correctamente'));

        $this->assertNotNull($pago->fresh()->renewal_notified_at);
        $this->assertSame('authorized', $preapproval->fresh()->status);
    }

    public function test_avisa_el_primer_fallo_y_reinicia_el_aviso_tras_un_cobro_exitoso(): void
    {
        $preapproval = $this->preapproval();
        $notifier = app(SubscriptionRecurringNotifier::class);

        $this->assertTrue($notifier->chargeFailed($preapproval));
        $this->assertFalse($notifier->chargeFailed($preapproval->fresh()));
        Mail::assertQueued(SubscriptionChargeFailed::class, 1);
        Mail::assertQueued(SubscriptionChargeFailed::class, fn ($mail) => str_contains($mail->render(), 'actualizar los datos de tu tarjeta'));

        $this->assertNotNull($preapproval->fresh()->first_failure_notified_at);

        // Un cobro aprobado limpia las marcas: el siguiente fallo vuelve a avisarse.
        $notifier->chargeApproved($preapproval->fresh(), $this->pago());

        $this->assertNull($preapproval->fresh()->first_failure_notified_at);
        $this->assertNull($preapproval->fresh()->grace_expired_notified_at);

        $this->assertTrue($notifier->chargeFailed($preapproval->fresh()));
        Mail::assertQueued(SubscriptionChargeFailed::class, 2);
    }

    public function test_avisa_la_suspension_al_terminar_la_gracia_una_sola_vez(): void
    {
        $preapproval = $this->preapproval([
            'next_payment_date' => now()->subDays(5)->toDateString(),
        ]);

        $notifier = app(SubscriptionRecurringNotifier::class);

        $this->assertTrue($preapproval->hasGraceExpired());
        $this->assertTrue($notifier->graceExpired($preapproval));
        $this->assertFalse($notifier->graceExpired($preapproval->fresh()));

        Mail::assertQueued(SubscriptionGraceExpired::class, 1);
        Mail::assertQueued(SubscriptionGraceExpired::class, fn ($mail) => str_contains($mail->render(), 'suspendida'));
        $this->assertNotNull($preapproval->fresh()->grace_expired_notified_at);
    }

    public function test_el_periodo_de_reintento_es_de_tres_dias(): void
    {
        $enGracia = $this->preapproval([
            'next_payment_date' => now()->subDays(2)->toDateString(),
        ]);

        $vencida = $this->preapproval([
            'preapproval_id' => 'preapproval-vencido',
            'next_payment_date' => now()->subDays(4)->toDateString(),
        ]);

        $this->assertSame(3, $enGracia->graceDays());
        $this->assertTrue($enGracia->isInGrace());
        $this->assertFalse($enGracia->hasGraceExpired());

        $this->assertTrue($vencida->hasGraceExpired());
        $this->assertFalse($vencida->isInGrace());
        $this->assertSame(
            $vencida->next_payment_date->copy()->endOfDay()->addDays(3)->toDateTimeString(),
            $vencida->graceEndsAt()->toDateTimeString()
        );
    }

    public function test_no_envia_si_el_alumno_no_tiene_correo(): void
    {
        $preapproval = $this->preapproval(['payer_email' => null]);

        $this->assertFalse(app(SubscriptionRecurringNotifier::class)->chargeFailed($preapproval));
        $this->assertFalse(app(SubscriptionRecurringNotifier::class)->graceExpired($preapproval));

        Mail::assertNothingQueued();
    }

    private function preapproval(array $overrides = []): AcaSubscriptionPreapproval
    {
        return AcaSubscriptionPreapproval::create(array_merge([
            'student_id' => 501,
            'subscription_id' => 3,
            'preapproval_id' => 'preapproval-' . uniqid(),
            'status' => 'authorized',
            'amount' => 49.90,
            'currency_id' => 'PEN',
            'next_payment_date' => now()->addMonth()->toDateString(),
            'payer_email' => 'alumno@example.com',
        ], $overrides));
    }

    private function pago(array $overrides = []): AcaSubscriptionPayment
    {
        return AcaSubscriptionPayment::create(array_merge([
            'student_id' => 501,
            'subscription_id' => 3,
            'date_start' => now()->toDateString(),
            'date_end' => now()->addMonth()->toDateString(),
            'amount' => 49.90,
            'authorized_payment_id' => 'invoice-' . uniqid(),
            'status' => 'approved',
            'source' => 'recurring',
            'paid_at' => now(),
        ], $overrides));
    }
}
