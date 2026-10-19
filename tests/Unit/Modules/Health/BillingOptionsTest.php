<?php

namespace Tests\Unit\Modules\Health;

use Modules\Health\Support\BillingOptions;
use Tests\TestCase;
use Tests\Unit\Modules\Health\Concerns\BuildsPatientChargeSchema;

/**
 * Opciones de facturación de los cobros.
 *
 * Lo que importa: los valores por defecto mantienen el comportamiento anterior
 * (gravado 10), un valor inválido no deja el sistema en un estado raro, y
 * guardar parte de las opciones no borra las demás.
 */
class BillingOptionsTest extends TestCase
{
    use BuildsPatientChargeSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropPatientChargeSchema();
        $this->createPatientChargeSchema();
    }

    protected function tearDown(): void
    {
        $this->dropPatientChargeSchema();

        parent::tearDown();
    }

    public function test_sin_configuracion_guardada_se_usa_el_comportamiento_anterior(): void
    {
        $options = BillingOptions::read();

        $this->assertSame('10', $options['igv_affectation']);
        $this->assertSame('ZZ', $options['unit_type']);
        $this->assertSame(2, $options['consultation_recobro_months']);
        $this->assertFalse($options['allow_cancel_with_document']);
    }

    public function test_los_valores_fuera_de_rango_se_acotan(): void
    {
        BillingOptions::save([
            'igv_affectation' => '99',
            'unit_type' => '   ',
            'consultation_recobro_months' => 999,
        ]);

        $options = BillingOptions::read();

        $this->assertSame('10', $options['igv_affectation'], 'una afectación inválida vuelve al default');
        $this->assertSame('ZZ', $options['unit_type']);
        $this->assertSame(60, $options['consultation_recobro_months']);

        BillingOptions::save(['consultation_recobro_months' => 0]);

        $this->assertSame(1, BillingOptions::read()['consultation_recobro_months']);
    }

    public function test_guardar_solo_algunas_opciones_no_borra_las_demas(): void
    {
        BillingOptions::save([
            'igv_affectation' => '20',
            'billing_description' => 'Cobros de Salud',
        ]);

        BillingOptions::save(['consultation_recobro_months' => 6]);

        $options = BillingOptions::read();

        $this->assertSame('20', $options['igv_affectation']);
        $this->assertSame('Cobros de Salud', $options['billing_description']);
        $this->assertSame(6, $options['consultation_recobro_months']);
    }
}
