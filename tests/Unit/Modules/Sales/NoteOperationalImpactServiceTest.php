<?php

namespace Tests\Unit\Modules\Sales;

use Modules\Sales\Services\NoteOperationalImpactService;
use Modules\Sales\Services\SaleStockService;
use PHPUnit\Framework\TestCase;

class NoteOperationalImpactServiceTest extends TestCase
{
    private function service(): NoteOperationalImpactService
    {
        return new NoteOperationalImpactService(new SaleStockService);
    }

    public function test_cash_share_suma_solo_el_metodo_efectivo(): void
    {
        $this->assertSame(120.5, $this->service()->cashShare([
            ['type' => 1, 'amount' => 120.50],
            ['type' => 2, 'amount' => 79.50],
        ]));
    }

    public function test_cash_share_sin_pagos_es_cero(): void
    {
        $this->assertSame(0.0, $this->service()->cashShare(null));
        $this->assertSame(0.0, $this->service()->cashShare([]));
    }

    public function test_prorratea_la_parte_pagada_en_efectivo(): void
    {
        // Venta de 200 pagada 100 efectivo + 100 Yape; nota de 50 => 25 en efectivo.
        $this->assertSame(25.0, $this->service()->prorateNoteCash(50.0, 200.0, [
            ['type' => 1, 'amount' => 100],
            ['type' => 2, 'amount' => 100],
        ]));
    }

    public function test_no_ajusta_si_la_venta_no_fue_en_efectivo(): void
    {
        $this->assertSame(0.0, $this->service()->prorateNoteCash(50.0, 200.0, [
            ['type' => 4, 'amount' => 200],
        ]));
    }

    public function test_el_ajuste_nunca_supera_el_total_de_la_nota(): void
    {
        $this->assertSame(50.0, $this->service()->prorateNoteCash(50.0, 100.0, [
            ['type' => 1, 'amount' => 100],
        ]));
    }

    public function test_totales_invalidos_no_generan_ajuste(): void
    {
        $this->assertSame(0.0, $this->service()->prorateNoteCash(0.0, 100.0, [['type' => 1, 'amount' => 100]]));
        $this->assertSame(0.0, $this->service()->prorateNoteCash(10.0, 0.0, [['type' => 1, 'amount' => 100]]));
    }
}
