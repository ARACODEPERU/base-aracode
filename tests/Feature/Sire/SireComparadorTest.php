<?php

namespace Tests\Feature\Sire;

use Modules\Sales\Services\Sire\SireComparadorService;
use Tests\TestCase;

/**
 * Comparación entre la propuesta de SUNAT y el registro del ERP: claves por
 * tipo/serie/número, faltantes en cada lado y diferencias de datos con
 * tolerancia de dos céntimos.
 */
class SireComparadorTest extends TestCase
{
    public function test_el_mismo_archivo_no_tiene_diferencias(): void
    {
        $texto = $this->ventas([
            ['serie' => 'F001', 'numero' => '1'],
            ['serie' => 'B002', 'numero' => '34', 'tipo' => '03', 'doc_tipo' => '1', 'doc_numero' => '44556677'],
        ]);

        $resultado = app(SireComparadorService::class)->comparar('ventas', $texto, $texto);

        $this->assertTrue($resultado['ok']);
        $this->assertSame(2, $resultado['resumen']['iguales']);
        $this->assertSame([], $resultado['diferencias']);
        $this->assertStringContainsString('coincide con la propuesta de SUNAT', $resultado['message']);
    }

    public function test_detecta_lo_que_sunat_tiene_y_el_erp_no(): void
    {
        $erp = $this->ventas([['serie' => 'F001', 'numero' => '1']]);
        $sunat = $this->ventas([
            ['serie' => 'F001', 'numero' => '1'],
            ['serie' => 'F001', 'numero' => '2'],
        ]);

        $resultado = app(SireComparadorService::class)->comparar('ventas', $erp, $sunat);

        $this->assertFalse($resultado['ok']);
        $this->assertSame(1, $resultado['resumen']['faltan_en_erp']);
        $this->assertSame(0, $resultado['resumen']['faltan_en_sunat']);
        $this->assertSame('F001-2', $resultado['faltan_en_erp'][0]['numero']);
    }

    public function test_detecta_lo_que_el_erp_tiene_y_sunat_no(): void
    {
        $erp = $this->ventas([
            ['serie' => 'F001', 'numero' => '1'],
            ['serie' => 'F001', 'numero' => '7'],
        ]);
        $sunat = $this->ventas([['serie' => 'F001', 'numero' => '1']]);

        $resultado = app(SireComparadorService::class)->comparar('ventas', $erp, $sunat);

        $this->assertFalse($resultado['ok']);
        $this->assertSame(1, $resultado['resumen']['faltan_en_sunat']);
        $this->assertSame('F001-7', $resultado['faltan_en_sunat'][0]['numero']);
    }

    public function test_los_importes_se_comparan_con_dos_centimos_de_tolerancia(): void
    {
        $servicio = app(SireComparadorService::class);
        $base = ['serie' => 'F001', 'numero' => '1'];

        $centimo = $servicio->comparar(
            'ventas',
            $this->ventas([$base + ['total' => 100.01]]),
            $this->ventas([$base + ['total' => 100.00]])
        );

        $this->assertTrue($centimo['ok']);

        $diferencia = $servicio->comparar(
            'ventas',
            $this->ventas([$base + ['total' => 100.05]]),
            $this->ventas([$base + ['total' => 100.00]])
        );

        $this->assertFalse($diferencia['ok']);
        $this->assertSame(1, $diferencia['resumen']['diferencias']);
        $this->assertSame('total', $diferencia['diferencias'][0]['campo']);
        $this->assertSame('100.00', $diferencia['diferencias'][0]['sunat']);
        $this->assertSame('100.05', $diferencia['diferencias'][0]['erp']);
    }

    public function test_compara_datos_del_cliente_y_la_moneda(): void
    {
        $resultado = app(SireComparadorService::class)->comparar(
            'ventas',
            $this->ventas([['serie' => 'F001', 'numero' => '1', 'nombre' => 'CLIENTE NUEVO SAC', 'moneda' => 'USD', 'tc' => '3.750']]),
            $this->ventas([['serie' => 'F001', 'numero' => '1', 'nombre' => 'CLIENTE ANTIGUO SAC', 'moneda' => 'PEN', 'tc' => '']])
        );

        $campos = array_column($resultado['diferencias'], 'campo');

        $this->assertFalse($resultado['ok']);
        $this->assertContains('nombre', $campos);
        $this->assertContains('moneda', $campos);
        $this->assertContains('tipo_cambio', $campos);
    }

    public function test_el_registro_de_compras_usa_el_numero_del_campo_10(): void
    {
        $linea = $this->compras(['serie' => 'F001', 'numero' => '123']);

        $resultado = app(SireComparadorService::class)->comparar('compras', $linea . $linea, $linea);

        $this->assertTrue($resultado['ok']);
        $this->assertSame(1, $resultado['resumen']['erp_lineas']);
        $this->assertSame(1, $resultado['resumen']['sunat_lineas']);
    }

    public function test_una_linea_ilegible_deja_la_comparacion_incompleta(): void
    {
        $resultado = app(SireComparadorService::class)->comparar(
            'ventas',
            $this->ventas([['serie' => 'F001', 'numero' => '1']]),
            "20611376031|ARACODE|202611|corta\r\n"
        );

        $this->assertFalse($resultado['ok']);
        $this->assertSame(1, $resultado['lineas_invalidas_sunat']);
        $this->assertStringContainsString('ilegibles', $resultado['message']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lineas
     */
    private function ventas(array $lineas): string
    {
        $texto = '';

        foreach ($lineas as $linea) {
            $datos = array_fill(0, 33, '');
            $datos[0] = '20611376031';
            $datos[1] = 'ARACODE SMART SOLUTIONS SAC';
            $datos[2] = '202611';
            $datos[4] = $linea['fecha'] ?? '18/11/2026';
            $datos[6] = $linea['tipo'] ?? '01';
            $datos[7] = $linea['serie'];
            $datos[8] = $linea['numero'];
            $datos[10] = $linea['doc_tipo'] ?? '6';
            $datos[11] = $linea['doc_numero'] ?? '20123456789';
            $datos[12] = $linea['nombre'] ?? 'CLIENTE PRUEBA SAC';
            $datos[14] = number_format((float) ($linea['gravada'] ?? 84.75), 2, '.', '');
            $datos[16] = number_format((float) ($linea['igv'] ?? 15.25), 2, '.', '');
            $datos[25] = number_format((float) ($linea['total'] ?? 100), 2, '.', '');
            $datos[26] = $linea['moneda'] ?? 'PEN';
            $datos[27] = $linea['tc'] ?? '';

            $texto .= implode('|', $datos) . "\r\n";
        }

        return $texto;
    }

    /**
     * @param  array<string, mixed>  $linea
     */
    private function compras(array $linea): string
    {
        $datos = array_fill(0, 37, '');
        $datos[0] = '20611376031';
        $datos[1] = 'ARACODE SMART SOLUTIONS SAC';
        $datos[2] = '202611';
        $datos[4] = '18/11/2026';
        $datos[6] = '01';
        $datos[7] = $linea['serie'];
        $datos[9] = $linea['numero'];
        $datos[11] = '6';
        $datos[12] = '20123456789';
        $datos[13] = 'PROVEEDOR PRUEBA SAC';
        $datos[14] = '84.75';
        $datos[15] = '15.25';
        $datos[24] = '100.00';
        $datos[25] = 'PEN';

        return implode('|', $datos) . "\r\n";
    }
}
