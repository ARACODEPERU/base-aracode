<?php

namespace Tests\Feature\Academic;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Academic\Entities\AcaSubscriptionPayment;
use Modules\Academic\Entities\AcaSubscriptionPreapproval;
use Modules\Academic\Entities\AcaSubscriptionType;
use Modules\Academic\Entities\MercadoPagoEvent;
use Tests\TestCase;

/**
 * Fase 1: estructura de datos de los cobros recurrentes mensuales.
 */
class AcaSubscriptionRecurringSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_existen_las_tablas_y_columnas_de_recurrencia(): void
    {
        $this->assertTrue(Schema::hasTable('aca_subscription_preapprovals'));
        $this->assertTrue(Schema::hasTable('mercadopago_events'));

        foreach (['recurring_enabled', 'mercadopago_plan_id'] as $columna) {
            $this->assertTrue(Schema::hasColumn('aca_subscription_types', $columna));
        }

        foreach (['authorized_payment_id', 'payment_id', 'preapproval_id', 'status', 'source', 'paid_at'] as $columna) {
            $this->assertTrue(Schema::hasColumn('aca_subscription_payments', $columna));
        }
    }

    public function test_un_plan_puede_habilitar_la_recurrencia(): void
    {
        $plan = AcaSubscriptionType::create([
            'title' => 'Plan mensual de prueba',
            'usine' => 'academic',
            'period' => 'Mensual',
            'status' => true,
            'recurring_enabled' => true,
            'mercadopago_plan_id' => 'plan-mp-123',
        ]);

        $this->assertTrue($plan->fresh()->recurring_enabled);
        $this->assertSame('plan-mp-123', $plan->fresh()->mercadopago_plan_id);
    }

    public function test_el_preapproval_guarda_datos_fiscales_y_estado(): void
    {
        $preapproval = AcaSubscriptionPreapproval::create([
            'student_id' => 99,
            'subscription_id' => 7,
            'preapproval_id' => '2c938084726fca480172750000000000',
            'status' => 'authorized',
            'amount' => 49.90,
            'currency_id' => 'PEN',
            'next_payment_date' => '2026-12-18',
            'invoice_data' => [
                'invoice_type' => 'boleta',
                'client_number' => '12345678',
                'client_rzn_social' => 'Alumno de Prueba',
            ],
        ]);

        $preapproval = $preapproval->fresh();

        $this->assertSame('boleta', $preapproval->invoice_data['invoice_type']);
        $this->assertSame('2026-12-18', $preapproval->next_payment_date->toDateString());
        $this->assertTrue($preapproval->isActive());

        $preapproval->update(['status' => 'cancelled']);

        $this->assertFalse($preapproval->fresh()->isActive());
    }

    public function test_el_pago_recurrente_se_marca_por_su_cobro_autorizado(): void
    {
        $pago = AcaSubscriptionPayment::create([
            'student_id' => 99,
            'subscription_id' => 7,
            'date_start' => '2026-11-18',
            'date_end' => '2026-12-17',
            'amount' => 49.90,
            'authorized_payment_id' => 'invoice-mp-1',
            'preapproval_id' => '2c938084726fca480172750000000000',
            'status' => 'approved',
            'source' => 'recurring',
            'paid_at' => '2026-11-18 10:00:00',
        ]);

        $this->assertSame('recurring', $pago->fresh()->source);
        $this->assertDatabaseHas('aca_subscription_payments', [
            'authorized_payment_id' => 'invoice-mp-1',
            'preapproval_id' => '2c938084726fca480172750000000000',
        ]);
    }

    public function test_el_evento_de_mercadopago_guarda_el_payload(): void
    {
        $evento = MercadoPagoEvent::create([
            'topic' => 'subscription_authorized_payment',
            'action' => 'created',
            'data_id' => 'invoice-mp-1',
            'payload' => ['type' => 'subscription_authorized_payment', 'data' => ['id' => 'invoice-mp-1']],
        ]);

        $this->assertSame('invoice-mp-1', $evento->fresh()->payload['data']['id']);
    }
}
