<?php

namespace Tests\Unit\Modules\Integrationhub;

use App\Services\IntegrationhubCronExpression;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Contrato del evaluador cron de las programaciones de Integrationhub.
 *
 * Además de la semántica (pasos, rangos, listas, domingo 0/7, día del mes Y
 * día de la semana), aquí se fija el rendimiento: `nextRunDate` debe resolver
 * una expresión anual en milisegundos, porque se calcula al guardar cada
 * programación y en cada tick del scheduler.
 */
class IntegrationhubCronExpressionTest extends TestCase
{
    private IntegrationhubCronExpression $cron;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cron = new IntegrationhubCronExpression();
    }

    public function test_valida_expresiones_de_cinco_campos(): void
    {
        $this->assertTrue($this->cron->isValid('* * * * *'));
        $this->assertTrue($this->cron->isValid('0 0 1 1 *'));
        $this->assertTrue($this->cron->isValid('*/15 0 * * 1-5'));
        $this->assertTrue($this->cron->isValid('0,30 8 * * *'));

        $this->assertFalse($this->cron->isValid('nope'), 'no es una expresión');
        $this->assertFalse($this->cron->isValid('* * * *'), 'le falta un campo');
        $this->assertFalse($this->cron->isValid('* * * * * *'), 'sobra un campo');
        $this->assertFalse($this->cron->isValid('61 * * * *'), 'minuto fuera de rango');
        $this->assertFalse($this->cron->isValid('a * * * *'), 'campo no numérico');
    }

    public function test_is_due_con_pasos_rangos_y_listas(): void
    {
        $this->assertTrue($this->cron->isDue('*/15 * * * *', Carbon::parse('2026-09-26 10:00')));
        $this->assertTrue($this->cron->isDue('*/15 * * * *', Carbon::parse('2026-09-26 10:30')));
        $this->assertFalse($this->cron->isDue('*/15 * * * *', Carbon::parse('2026-09-26 10:07')));

        $this->assertTrue($this->cron->isDue('0,30 8 * * *', Carbon::parse('2026-09-26 08:30')));
        $this->assertFalse($this->cron->isDue('0,30 8 * * *', Carbon::parse('2026-09-26 08:15')));

        // 2026-09-28 es lunes.
        $this->assertTrue($this->cron->isDue('30 8 * * 1', Carbon::parse('2026-09-28 08:30')));
        $this->assertFalse($this->cron->isDue('30 8 * * 1', Carbon::parse('2026-09-28 08:31')));
        $this->assertFalse($this->cron->isDue('30 8 * * 1', Carbon::parse('2026-09-27 08:30')));

        $this->assertTrue($this->cron->isDue('0 8-18 * * *', Carbon::parse('2026-09-26 14:00')));
        $this->assertFalse($this->cron->isDue('0 8-18 * * *', Carbon::parse('2026-09-26 19:00')));

        $this->assertTrue($this->cron->isDue('0 0 1 * *', Carbon::parse('2026-10-01 00:00')));
        $this->assertFalse($this->cron->isDue('0 0 1 * *', Carbon::parse('2026-10-02 00:00')));
    }

    public function test_el_domingo_es_0_y_7(): void
    {
        // 2026-09-27 es domingo.
        $sunday = Carbon::parse('2026-09-27 00:00');
        $monday = Carbon::parse('2026-09-28 00:00');

        $this->assertTrue($this->cron->isDue('0 0 * * 0', $sunday));
        $this->assertTrue($this->cron->isDue('0 0 * * 7', $sunday));
        $this->assertFalse($this->cron->isDue('0 0 * * 7', $monday));
    }

    public function test_dia_del_mes_y_dia_semana_se_combinan_con_and(): void
    {
        // 2026-06-01 es lunes: solo ese día cumple día=1 Y lunes.
        $this->assertTrue($this->cron->isDue('0 0 1 * 1', Carbon::parse('2026-06-01 00:00')));
        $this->assertFalse($this->cron->isDue('0 0 1 * 1', Carbon::parse('2026-06-02 00:00')), 'día 2');
        $this->assertFalse($this->cron->isDue('0 0 1 * 1', Carbon::parse('2026-06-08 00:00')), 'lunes pero día 8');
    }

    public function test_next_run_date_salta_al_siguiente_disparo(): void
    {
        $this->assertEquals(
            Carbon::parse('2026-09-26 10:01'),
            $this->cron->nextRunDate('* * * * *', Carbon::parse('2026-09-26 10:00'))
        );

        $this->assertEquals(
            Carbon::parse('2026-09-26 10:05'),
            $this->cron->nextRunDate('*/5 * * * *', Carbon::parse('2026-09-26 10:02'))
        );

        $this->assertEquals(
            Carbon::parse('2026-09-27 00:00'),
            $this->cron->nextRunDate('0 0 * * *', Carbon::parse('2026-09-26 10:00'))
        );

        // Semanal a los lunes.
        $this->assertEquals(
            Carbon::parse('2026-09-28 08:30'),
            $this->cron->nextRunDate('30 8 * * 1', Carbon::parse('2026-09-26 10:00'))
        );
    }

    public function test_next_run_date_resuelve_expresiones_raras_en_milisegundos(): void
    {
        $start = microtime(true);

        $this->assertEquals(
            Carbon::parse('2027-01-01 00:00'),
            $this->cron->nextRunDate('0 0 1 1 *', Carbon::parse('2026-09-26 10:00'))
        );

        // 2028 es bisiesto: el 29 de febrero está a más de un año vista.
        $this->assertEquals(
            Carbon::parse('2028-02-29 00:00'),
            $this->cron->nextRunDate('0 0 29 2 *', Carbon::parse('2026-01-01 00:00'))
        );

        $elapsed = microtime(true) - $start;

        $this->assertLessThan(1.0, $elapsed, 'calcular la próxima ejecución no debe tardar segundos');
    }

    public function test_next_run_date_con_expresion_invalida_es_null(): void
    {
        $this->assertNull($this->cron->nextRunDate('nope', Carbon::parse('2026-09-26 10:00')));
        $this->assertNull($this->cron->nextRunDate(null));
        $this->assertFalse($this->cron->isDue('nope', Carbon::parse('2026-09-26 10:00')));
    }
}
