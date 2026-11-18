<?php

namespace Tests\Feature\Academic;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Academic\Emails\SubscriptionGraceExpired;
use Modules\Academic\Entities\AcaSubscriptionPreapproval;
use Tests\TestCase;

/**
 * El barrido diario de vencimientos debe respetar los 3 días de reintento de
 * las suscripciones recurrentes antes de quitar el acceso.
 */
class CheckExpiredSubscriptionsGraceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config(['services.mercadopago.subscription_grace_days' => 3]);
    }

    public function test_mantiene_activa_la_suscripcion_durante_los_dias_de_gracia(): void
    {
        $this->suscripcionVencida(501, 3, now()->subDay()->toDateString());
        $this->preapproval(['next_payment_date' => now()->subDay()->toDateString()]);

        $this->artisan('academic:check-expired-subscriptions')->assertExitCode(0);

        $this->assertSame(1, (int) DB::table('aca_student_subscriptions')->value('status'));
        Mail::assertNothingQueued();
    }

    public function test_suspende_y_avisa_cuando_termina_la_gracia(): void
    {
        $this->suscripcionVencida(502, 3, now()->subDays(5)->toDateString());
        $this->preapproval([
            'student_id' => 502,
            'next_payment_date' => now()->subDays(5)->toDateString(),
        ]);

        $this->artisan('academic:check-expired-subscriptions')->assertExitCode(0);

        $this->assertSame(0, (int) DB::table('aca_student_subscriptions')->value('status'));
        Mail::assertQueued(SubscriptionGraceExpired::class, 1);
    }

    public function test_las_suscripciones_sin_recurrencia_se_siguen_venciendo_igual(): void
    {
        $this->suscripcionVencida(503, 3, now()->subDay()->toDateString());

        $this->artisan('academic:check-expired-subscriptions')->assertExitCode(0);

        $this->assertSame(0, (int) DB::table('aca_student_subscriptions')->value('status'));
        Mail::assertNothingQueued();
    }

    private function suscripcionVencida(int $studentId, int $subscriptionId, string $dateEnd): void
    {
        DB::table('aca_student_subscriptions')->insert([
            'student_id' => $studentId,
            'subscription_id' => $subscriptionId,
            'date_start' => now()->subMonth()->toDateString(),
            'date_end' => $dateEnd,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
            'payer_email' => 'alumno@example.com',
        ], $overrides));
    }
}
