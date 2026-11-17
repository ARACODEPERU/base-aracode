<?php

namespace Tests\Unit\Treasury;

use Illuminate\Validation\ValidationException;
use Modules\Treasury\Services\FeeCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FeeCalculatorTest extends TestCase
{
    public function test_itf_no_se_cobra_bajo_el_umbral(): void
    {
        $calc = new FeeCalculator();

        $this->assertSame(0.0, $calc->itf(999.99));
    }

    public function test_itf_se_cobra_al_alcanzar_el_umbral(): void
    {
        $calc = new FeeCalculator();

        // 0.005 % de 1300 = 0.065 -> redondeado a 0.07 (2 decimales).
        $this->assertSame(0.07, $calc->itf(1300.00));
        $this->assertSame(0.05, $calc->itf(1000.00));
    }

    public function test_comision_mercado_pago_con_igv(): void
    {
        $calc = new FeeCalculator();

        // Fijo S/ 1 + 3.49 % sobre 100 = 4.49; IGV 18 % = 0.81 (redondeado).
        $result = $calc->commission(100, 1.0, 3.49, true);

        $this->assertSame(4.49, $result['comision']);
        $this->assertSame(0.81, $result['igv']);
        $this->assertSame(5.30, $result['total']);
    }

    public function test_comision_cero_sin_igv(): void
    {
        $calc = new FeeCalculator();

        $result = $calc->commission(1300, 0, 0, false);

        $this->assertSame(0.0, $result['total']);
    }

    public function test_formula_evalua_variables_y_parentesis(): void
    {
        $calc = new FeeCalculator();

        $result = $calc->commission(100, 1.0, 3.49, false, '(fijo + monto * porcentaje / 100) * (1 + igv)');

        // (1 + 3.49) * 1.18 = 5.3 (aprox.)
        $this->assertSame(5.30, $result['comision']);
    }

    #[DataProvider('formulasInvalidas')]
    public function test_formula_rechaza_entradas_no_permitidas(string $formula): void
    {
        $calc = new FeeCalculator();

        $this->expectException(ValidationException::class);

        $calc->validateFormula($formula);
    }

    public static function formulasInvalidas(): array
    {
        return [
            'variable desconocida' => ['monto + saldo'],
            'llamada a funcion' => ['system("ls")'],
            'division entre cero' => ['monto / 0'],
            'paréntesis sin cerrar' => ['(monto + 1'],
            'caracteres extra' => ['monto; 1'],
            'vacia' => [''],
        ];
    }
}
