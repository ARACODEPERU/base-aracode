<?php

namespace App\Helpers\Invoice\Documents;

use App\Helpers\Invoice\Sunat\SunatFileStorage;
use App\Helpers\Invoice\Sunat\SunatResilience;
use App\Helpers\Invoice\Sunat\SunatTicketQuery;
use App\Helpers\Invoice\Util;
use App\Models\Company as MyCompany;
use DateTime;
use DateTimeInterface;
use Greenter\Model\Client\Client;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Despatch\AdditionalDoc;
use Greenter\Model\Despatch\Despatch;
use Greenter\Model\Despatch\DespatchDetail;
use Greenter\Model\Despatch\Direction;
use Greenter\Model\Despatch\Driver;
use Greenter\Model\Despatch\Shipment;
use Greenter\Model\Despatch\Transportist;
use Greenter\Model\Despatch\Vehicle;
use Modules\Sales\Entities\GuiaRemision as GuiaRemisionModel;
use Throwable;

/**
 * Guía de Remisión Electrónica (remitente) con Greenter.
 *
 * El mapeo vive en `buildDespatch()`: recibe el modelo local y devuelve el
 * `Greenter\Model\Despatch\Despatch` listo para firmar. El envío reutiliza la
 * capa de resiliencia (nunca bloquea el registro) y el almacenamiento
 * centralizado de XML/CDR.
 */
class GuiaRemision
{
    public function __construct(
        private ?Util $util = null,
        private ?SunatResilience $resilience = null,
        private ?SunatFileStorage $files = null,
        private ?SunatTicketQuery $tickets = null,
    ) {
    }

    /**
     * Envía la guía a SUNAT y deja el resultado en el registro local.
     *
     * @return array<string, mixed>
     */
    public function create(GuiaRemisionModel $guia): array
    {
        $guia->loadMissing('details');

        if ($guia->details->isEmpty()) {
            return $this->payload($guia, [
                'success' => false,
                'message' => 'La guía no tiene bienes que trasladar: agrega al menos un detalle.',
            ]);
        }

        $despatch = $this->buildDespatch($guia);

        $util = $this->util ?? Util::getInstance();
        $resilience = $this->resilience ?? app(SunatResilience::class);
        $files = $this->files ?? app(SunatFileStorage::class);

        try {
            $see = $util->getSee();
            // La guía usa un WebService distinto al de facturación.
            $see->setService($util->getGuiaService());
        } catch (Throwable $e) {
            $guia->status = GuiaRemisionModel::STATUS_CONTINGENCIA;
            $guia->sunat_send_status = 'reintento';
            $guia->response_description = SunatResilience::FRIENDLY_QUEUED;
            $guia->notes = json_encode(['error' => mb_substr($e->getMessage(), 0, 1500)], JSON_UNESCAPED_UNICODE);
            $guia->save();

            return $this->payload($guia, ['success' => true, 'queued' => true, 'message' => SunatResilience::FRIENDLY_QUEUED]);
        }

        $send = $resilience->sendWith($see, $despatch, $util);
        $res = $send['outcome'];

        $guia->xml = $files->storeXml($despatch->getName(), $send['xml']) ?: $guia->xml;

        if ($res->isSuccess()) {
            $cdr = $res->getCdrResponse();
            $stored = $files->storeCdr($despatch->getName(), $res->getCdrZip());

            if ($stored['zip'] !== null) {
                $guia->cdr = $stored['zip'];
            }

            if ($cdr !== null) {
                $code = (string) $cdr->getCode();

                $guia->status = $code === '0'
                    ? GuiaRemisionModel::STATUS_ACEPTADO
                    : GuiaRemisionModel::STATUS_RECHAZADO;
                $guia->response_code = mb_substr($code, 0, 10);
                $guia->response_description = (string) $cdr->getDescription();

                if ($cdr->getNotes()) {
                    $guia->notes = json_encode($cdr->getNotes(), JSON_UNESCAPED_UNICODE);
                }
            } else {
                // Envío asíncrono: SUNAT devolvió ticket y el CDR se consulta después.
                $guia->status = GuiaRemisionModel::STATUS_ENVIADO;
                $guia->ticket = $res->getTicket();
                $guia->response_description = 'Guía enviada a SUNAT. Ticket: '.$guia->ticket.'. Pendiente de constancia.';
            }

            $guia->sunat_send_status = 'enviado';
            $guia->save();

            return $this->payload($guia, [
                'success' => true,
                'ticket' => $guia->ticket,
                'has_cdr' => $guia->cdr !== null,
            ]);
        }

        $failure = $resilience->describeFailure($res);

        $guia->response_code = mb_substr((string) ($res->getError()?->getCode() ?? ''), 0, 10);
        $guia->response_description = $failure['friendly'];
        $guia->notes = json_encode(['error' => $failure['technical'], 'kind' => $failure['kind']], JSON_UNESCAPED_UNICODE);
        $guia->sunat_send_status = $failure['retryable'] ? 'reintento' : 'rechazado';
        $guia->status = $failure['retryable']
            ? GuiaRemisionModel::STATUS_CONTINGENCIA
            : GuiaRemisionModel::STATUS_RECHAZADO;
        $guia->save();

        return $this->payload($guia, [
            'success' => $failure['retryable'],
            'queued' => $failure['retryable'],
            'message' => $failure['friendly'],
        ]);
    }

    /**
     * Consulta el ticket pendiente y actualiza la guía.
     *
     * @return array<string, mixed>
     */
    public function checkTicket(GuiaRemisionModel $guia): array
    {
        if (empty($guia->ticket)) {
            return $this->payload($guia, [
                'success' => false,
                'message' => 'La guía todavía no tiene ticket de SUNAT. Envíala primero.',
            ]);
        }

        $result = ($this->tickets ?? app(SunatTicketQuery::class))->query($guia->ticket);
        $state = $result['state'];
        $files = $this->files ?? app(SunatFileStorage::class);

        if ($state === SunatTicketQuery::ERROR) {
            return $this->payload($guia, [
                'success' => false,
                'message' => $result['message'],
            ]);
        }

        if ($state === SunatTicketQuery::EN_PROCESO) {
            $guia->response_description = 'SUNAT sigue procesando la guía. Vuelve a consultar más tarde.';
            $guia->save();

            return $this->payload($guia, ['success' => true, 'processing' => true]);
        }

        if ($state === SunatTicketQuery::NO_EXISTE) {
            $guia->response_code = '0127';
            $guia->response_description = 'El ticket no existe en SUNAT: vuelve a enviar la guía.';
            $guia->ticket = null;
            $guia->save();

            return $this->payload($guia, ['success' => false]);
        }

        $stored = $files->storeCdr($this->name($guia), $result['cdr_zip'] ?? null);

        if ($stored['zip'] !== null) {
            $guia->cdr = $stored['zip'];
        }

        $guia->status = $state === SunatTicketQuery::ACEPTADO
            ? GuiaRemisionModel::STATUS_ACEPTADO
            : GuiaRemisionModel::STATUS_RECHAZADO;
        $guia->response_code = mb_substr((string) ($result['cdr_code'] ?? ''), 0, 10);
        $guia->response_description = (string) (($result['cdr_description'] ?? null) ?: $result['message']);

        if (! empty($result['notes'])) {
            $guia->notes = json_encode($result['notes'], JSON_UNESCAPED_UNICODE);
        }

        $guia->save();

        return $this->payload($guia, ['success' => true, 'has_cdr' => $guia->cdr !== null]);
    }

    /**
     * Mapeo de la guía local al documento de Greenter.
     *
     * Este es el punto único a extender para nuevos casos (traslado entre
     * establecimientos, importación, exportación): solo se agregan campos
     * opcionales al modelo y se completan aquí.
     */
    public function buildDespatch(GuiaRemisionModel $guia, ?Company $emisor = null): Despatch
    {
        $company = $emisor ?? $this->company();

        $envio = (new Shipment)
            ->setCodTraslado($guia->cod_traslado ?: '01')
            ->setDesTraslado($guia->motivoTraslado())
            ->setModTraslado($guia->mod_traslado ?: '02')
            ->setFecTraslado($this->date($guia->fecha_emision))
            ->setPesoTotal((float) $guia->peso_total)
            ->setUndPesoTotal($guia->und_peso_total ?: 'KGM')
            ->setNumBultos($guia->num_bultos !== null ? (int) $guia->num_bultos : null)
            ->setPartida($this->direction($guia->partida_ubigeo, $guia->partida_direccion, $guia->partida_cod_local, $guia->partida_ruc))
            ->setLlegada($this->direction($guia->llegada_ubigeo, $guia->llegada_direccion, $guia->llegada_cod_local, $guia->llegada_ruc));

        $indicadores = $guia->indicadores ?: [];

        if (empty($indicadores) && ($guia->mod_traslado ?: '02') === '02') {
            // Transporte privado: SUNAT exige el indicador de vehículo/conductor.
            $indicadores = ['SUNAT_Envio_IndicadorTrasladoVehiculoM1L'];
        }

        if (! empty($indicadores)) {
            $envio->setIndicadores($indicadores);
        }

        if ($guia->sustento_peso) {
            $envio->setSustentoPeso($guia->sustento_peso);
        }

        $envio->setTransportista($this->transportista($guia, $company));

        if ($guia->vehiculo_placa) {
            $envio->setVehiculo((new Vehicle)
                ->setPlaca($guia->vehiculo_placa)
                ->setNroCirculacion($guia->vehiculo_nro_circulacion)
                ->setCodEmisor($guia->vehiculo_cod_emisor)
                ->setNroAutorizacion($guia->vehiculo_nro_autorizacion));
        }

        if ($guia->conductor_num_doc) {
            $envio->setChoferes([
                (new Driver)
                    ->setTipo('Principal')
                    ->setTipoDoc($guia->conductor_tipo_doc ?: '1')
                    ->setNroDoc($guia->conductor_num_doc)
                    ->setNombres($guia->conductor_nombres)
                    ->setApellidos($guia->conductor_apellidos)
                    ->setLicencia($guia->conductor_licencia),
            ]);
        }

        $despatch = (new Despatch)
            ->setVersion('2022')
            ->setTipoDoc($guia->tipo_doc ?: '09')
            ->setSerie($guia->serie)
            ->setCorrelativo($guia->correlativo)
            ->setFechaEmision($this->date($guia->fecha_emision))
            ->setCompany($company)
            ->setDestinatario($this->client($guia))
            ->setObservacion($guia->observacion)
            ->setEnvio($envio);

        if ($guia->tercero_num_doc) {
            $despatch->setTercero((new Client)
                ->setTipoDoc($guia->tercero_tipo_doc ?: '6')
                ->setNumDoc($guia->tercero_num_doc)
                ->setRznSocial($guia->tercero_razon_social));
        }

        if ($guia->comprador_num_doc) {
            $despatch->setComprador((new Client)
                ->setTipoDoc($guia->comprador_tipo_doc ?: '6')
                ->setNumDoc($guia->comprador_num_doc)
                ->setRznSocial($guia->comprador_razon_social));
        }

        if ($guia->doc_relacionado_numero) {
            $despatch->setAddDocs([
                (new AdditionalDoc)
                    ->setTipo($guia->doc_relacionado_tipo ?: '01')
                    ->setTipoDesc('FACTURA O BOLETA')
                    ->setNro($guia->doc_relacionado_numero)
                    ->setEmisor($company->getRuc()),
            ]);
        }

        $items = [];

        foreach ($guia->details as $detail) {
            $items[] = (new DespatchDetail)
                ->setCodigo($detail->codigo)
                ->setDescripcion($detail->descripcion)
                ->setUnidad($detail->unidad ?: 'NIU')
                ->setCantidad((float) $detail->cantidad)
                ->setCodProdSunat($detail->cod_prod_sunat);
        }

        return $despatch->setDetails($items);
    }

    private function company(): Company
    {
        $my = MyCompany::first();
        $province = $my->district->province;
        $department = $province->department;

        return (new Company)
            ->setRuc($my->ruc)
            ->setRazonSocial($my->business_name)
            ->setNombreComercial($my->tradename)
            ->setAddress((new Address)
                ->setUbigueo($my->ubigeo)
                ->setDepartamento($department->name)
                ->setProvincia($province->name)
                ->setDistrito($my->district->name)
                ->setUrbanizacion('-')
                ->setDireccion($my->fiscal_address));
    }

    private function client(GuiaRemisionModel $guia): Client
    {
        return (new Client)
            ->setTipoDoc($guia->destinatario_tipo_doc)
            ->setNumDoc($guia->destinatario_num_doc)
            ->setRznSocial($guia->destinatario_razon_social)
            ->setEmail($guia->destinatario_email);
    }

    private function direction(?string $ubigeo, ?string $direccion, ?string $codLocal = null, ?string $ruc = null): Direction
    {
        $direction = new Direction($ubigeo, $direccion);

        if ($codLocal) {
            $direction->setCodLocal($codLocal);
        }

        if ($ruc) {
            $direction->setRuc($ruc);
        }

        return $direction;
    }

    private function transportista(GuiaRemisionModel $guia, Company $company): Transportist
    {
        $esPrivado = ($guia->mod_traslado ?: '02') === '02';

        return (new Transportist)
            // En transporte privado el transportista es el propio emisor.
            ->setTipoDoc($esPrivado && empty($guia->transportista_num_doc) ? '6' : ($guia->transportista_tipo_doc ?: '6'))
            ->setNumDoc($esPrivado && empty($guia->transportista_num_doc) ? $company->getRuc() : $guia->transportista_num_doc)
            ->setRznSocial($esPrivado && empty($guia->transportista_razon_social) ? $company->getRazonSocial() : $guia->transportista_razon_social)
            ->setNroMtc($guia->transportista_nro_mtc);
    }

    private function date(mixed $value): DateTime
    {
        if ($value instanceof DateTimeInterface) {
            return new DateTime($value->format('Y-m-d H:i:s'));
        }

        return new DateTime((string) $value);
    }

    private function name(GuiaRemisionModel $guia): string
    {
        return trim($guia->serie.'-'.$guia->correlativo, '-');
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function payload(GuiaRemisionModel $guia, array $extra = []): array
    {
        return array_merge([
            'success' => $guia->status === GuiaRemisionModel::STATUS_ACEPTADO,
            'queued' => $guia->status === GuiaRemisionModel::STATUS_CONTINGENCIA,
            'status' => $guia->status,
            'code' => $guia->response_code,
            'message' => $guia->response_description,
            'ticket' => $guia->ticket,
            'has_cdr' => ! empty($guia->cdr),
            'guia_id' => $guia->id,
        ], $extra);
    }
}
