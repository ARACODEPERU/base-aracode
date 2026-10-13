<?php

namespace Tests\Unit\Socialevents;

use Modules\Socialevents\Services\SportsOrganizationService;
use PHPUnit\Framework\TestCase;

class SportsOrganizationPricingTest extends TestCase
{
    public function test_calcula_la_tarifa_por_cada_equipo_del_partido(): void
    {
        $service = new SportsOrganizationService();

        self::assertSame(10.0, $service->calculateMatchCharge(2, 5.0));
        self::assertSame(60.0, $service->calculateMatchCharge(12, 5.0));
        self::assertSame(0.0, $service->calculateMatchCharge(2, 0.0));
    }
}
