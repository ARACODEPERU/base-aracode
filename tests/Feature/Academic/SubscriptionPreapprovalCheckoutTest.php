<?php

namespace Tests\Feature\Academic;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Academic\Entities\AcaStudentSubscription;
use Modules\Academic\Entities\AcaSubscriptionPayment;
use Modules\Academic\Entities\AcaSubscriptionPreapproval;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Academic\Operations\StudentSubscription;
use Modules\Onlineshop\Entities\OnliSale;
use Tests\TestCase;

/**
 * Alta recurrente: la venta local se registra a partir del preapproval
 * autorizado y deja su cobro marcado como recurrente.
 */
class SubscriptionPreapprovalCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $personId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->personId = (int) DB::table('people')->insertGetId([
            'document_type_id' => '1',
            'full_name' => 'Alumno Prueba',
            'number' => '44556677',
            'email' => 'alumno@example.com',
            'telephone' => '999999999',
            'address' => 'Av. Prueba 1',
            'ubigeo' => '150101',
            'ubigeo_description' => 'LIMA-LIMA-LIMA',
            'is_client' => true,
            'gender' => 'M',
            'country_id' => 1,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->user = User::factory()->create(['email_verified_at' => now(), 'person_id' => $this->personId]);
        $this->user->local_id = 1;
        $this->user->save();

        $this->actingAs($this->user);
    }

    public function test_la_venta_recurrente_crea_la_suscripcion_y_su_primer_cobro(): void
    {
        $plan = AcaSubscriptionType::create([
            'title' => 'Plan Mensual',
            'usine' => 'academic',
            'period' => 'Mensual',
            'status' => true,
            'recurring_enabled' => true,
            'prices' => [['currency' => 'PEN', 'amount' => 120]],
        ]);

        $preapproval = AcaSubscriptionPreapproval::create([
            'student_id' => 9001,
            'subscription_id' => $plan->id,
            'preapproval_id' => 'preapproval-checkout-1',
            'status' => 'authorized',
            'amount' => 120,
            'currency_id' => 'PEN',
            'payer_email' => 'alumno@example.com',
            'raw' => ['id' => 'preapproval-checkout-1', 'status' => 'authorized'],
        ]);

        $sale = (new StudentSubscription($plan->id))->process([
            'transaction_amount' => 120,
            'payer' => ['identification' => ['type' => '1', 'number' => '44556677']],
            'issuer_id' => null,
            'payment_method_id' => 'visa',
            'personInvoice' => [
                'document_type' => 'boleta',
                'razonSocial' => 'Alumno Prueba',
                'ruc' => '44556677',
            ],
        ], (object) ['id' => $preapproval->preapproval_id, 'status' => 'approved'], $preapproval);

        $this->assertInstanceOf(OnliSale::class, $sale);
        $this->assertSame('preapproval-checkout-1', $sale->mercado_payment_id);

        $suscripcion = AcaStudentSubscription::where('subscription_id', $plan->id)->firstOrFail();
        $this->assertTrue((bool) $suscripcion->status);
        $this->assertSame(now()->toDateString(), (string) $suscripcion->date_start);

        $cobro = AcaSubscriptionPayment::where('preapproval_id', 'preapproval-checkout-1')->firstOrFail();
        $this->assertSame('recurring', $cobro->source);
        $this->assertSame('authorized', $cobro->status);
        $this->assertEqualsWithDelta(120, (float) $cobro->amount, 0.001);
    }
}
