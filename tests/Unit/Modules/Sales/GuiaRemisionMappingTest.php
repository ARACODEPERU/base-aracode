<?php

namespace Tests\Unit\Modules\Sales;

use App\Helpers\Invoice\Documents\GuiaRemision as GuiaRemisionService;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Modules\Sales\Entities\GuiaRemision;
use Modules\Sales\Entities\GuiaRemisionDetalle;
use Tests\TestCase;

class GuiaRemisionMappingTest extends TestCase
{
    private function emisor(): Company
    {
        return (new Company)
            ->setRuc('20123456789')
            ->setRazonSocial('EMPRESA DEMO SAC')
            ->setNombreComercial('EMPRESA DEMO')
            ->setAddress((new Address)
                ->setUbigueo('150101')
                ->setDepartamento('LIMA')
                ->setProvincia('LIMA')
                ->setDistrito('LIMA')
                ->setUrbanizacion('-')
                ->setDireccion('Av. Siempre Viva 123'));
    }

    private function guia(): GuiaRemision
    {
        $guia = new GuiaRemision([
            'tipo_doc' => '09',
            'serie' => 'T001',
            'correlativo' => '00000001',
            'fecha_emision' => '2026-11-06 10:30:00',
            'cod_traslado' => '01',
            'des_traslado' => null, // usa el catálogo
            'mod_traslado' => '02',
            'peso_total' => 25.5,
            'und_peso_total' => 'KGM',
            'num_bultos' => 2,
            'partida_ubigeo' => '150101',
            'partida_direccion' => 'Av. Partida 100',
            'llegada_ubigeo' => '150203',
            'llegada_direccion' => 'Jr. Llegada 200',
            'destinatario_tipo_doc' => '6',
            'destinatario_num_doc' => '20999999999',
            'destinatario_razon_social' => 'CLIENTE SAC',
            'transportista_tipo_doc' => '6',
            'transportista_num_doc' => '20111111111',
            'transportista_razon_social' => 'TRANSPORTES UNO SAC',
            'transportista_nro_mtc' => 'MTC-123',
            'vehiculo_placa' => 'ABC-123',
            'vehiculo_nro_circulacion' => 'CIR-9',
            'conductor_tipo_doc' => '1',
            'conductor_num_doc' => '12345678',
            'conductor_nombres' => 'JUAN',
            'conductor_apellidos' => 'PEREZ',
            'conductor_licencia' => 'Q-12345678',
            'doc_relacionado_tipo' => '01',
            'doc_relacionado_numero' => 'F001-123',
            'observacion' => 'Entrega en almacén',
        ]);

        $guia->setRelation('details', collect([
            new GuiaRemisionDetalle(['codigo' => 'P001', 'descripcion' => 'Caja grande', 'unidad' => 'NIU', 'cantidad' => 3, 'cod_prod_sunat' => '10101501']),
            new GuiaRemisionDetalle(['codigo' => 'P002', 'descripcion' => 'Bolsa', 'unidad' => 'NIU', 'cantidad' => 5]),
        ]));

        return $guia;
    }

    public function test_mapea_la_cabecera_de_la_guia(): void
    {
        $despatch = (new GuiaRemisionService)->buildDespatch($this->guia(), $this->emisor());

        $this->assertSame('09', $despatch->getTipoDoc());
        $this->assertSame('T001', $despatch->getSerie());
        $this->assertSame('00000001', $despatch->getCorrelativo());
        $this->assertSame('2022', $despatch->getVersion());
        $this->assertSame('20123456789-09-T001-00000001', $despatch->getName());
        $this->assertSame('Entrega en almacén', $despatch->getObservacion());
        $this->assertSame('20999999999', $despatch->getDestinatario()->getNumDoc());
    }

    public function test_mapea_el_traslado_con_sus_puntos_y_pesos(): void
    {
        $envio = (new GuiaRemisionService)->buildDespatch($this->guia(), $this->emisor())->getEnvio();

        $this->assertSame('01', $envio->getCodTraslado());
        $this->assertSame('Venta', $envio->getDesTraslado());
        $this->assertSame('02', $envio->getModTraslado());
        $this->assertSame(25.5, $envio->getPesoTotal());
        $this->assertSame('KGM', $envio->getUndPesoTotal());
        $this->assertSame(2, $envio->getNumBultos());
        $this->assertSame('150101', $envio->getPartida()->getUbigueo());
        $this->assertSame('Av. Partida 100', $envio->getPartida()->getDireccion());
        $this->assertSame('150203', $envio->getLlegada()->getUbigueo());
        $this->assertSame('Jr. Llegada 200', $envio->getLlegada()->getDireccion());
        $this->assertContains('SUNAT_Envio_IndicadorTrasladoVehiculoM1L', $envio->getIndicadores());
    }

    public function test_mapea_transportista_vehiculo_y_conductor(): void
    {
        $envio = (new GuiaRemisionService)->buildDespatch($this->guia(), $this->emisor())->getEnvio();

        $this->assertSame('6', $envio->getTransportista()->getTipoDoc());
        $this->assertSame('20111111111', $envio->getTransportista()->getNumDoc());
        $this->assertSame('TRANSPORTES UNO SAC', $envio->getTransportista()->getRznSocial());
        $this->assertSame('MTC-123', $envio->getTransportista()->getNroMtc());
        $this->assertSame('ABC-123', $envio->getVehiculo()->getPlaca());
        $this->assertSame('CIR-9', $envio->getVehiculo()->getNroCirculacion());

        $chofer = $envio->getChoferes()[0];
        $this->assertSame('Principal', $chofer->getTipo());
        $this->assertSame('12345678', $chofer->getNroDoc());
        $this->assertSame('JUAN', $chofer->getNombres());
        $this->assertSame('PEREZ', $chofer->getApellidos());
        $this->assertSame('Q-12345678', $chofer->getLicencia());
    }

    public function test_mapea_los_bienes_y_el_comprobante_relacionado(): void
    {
        $despatch = (new GuiaRemisionService)->buildDespatch($this->guia(), $this->emisor());

        $this->assertCount(2, $despatch->getDetails());
        $this->assertSame('P001', $despatch->getDetails()[0]->getCodigo());
        $this->assertSame('NIU', $despatch->getDetails()[0]->getUnidad());
        $this->assertSame(3.0, $despatch->getDetails()[0]->getCantidad());
        $this->assertSame('10101501', $despatch->getDetails()[0]->getCodProdSunat());

        $this->assertSame('F001-123', $despatch->getAddDocs()[0]->getNro());
        $this->assertSame('20123456789', $despatch->getAddDocs()[0]->getEmisor());
    }

    public function test_el_motivo_se_toma_del_catalogo_sunat(): void
    {
        $guia = $this->guia();
        $guia->cod_traslado = '04';

        $envio = (new GuiaRemisionService)->buildDespatch($guia, $this->emisor())->getEnvio();

        $this->assertSame('Traslado entre establecimientos de la misma empresa', $envio->getDesTraslado());
    }
}
