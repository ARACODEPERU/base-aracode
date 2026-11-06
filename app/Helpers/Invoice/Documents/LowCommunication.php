<?php

namespace App\Helpers\Invoice\Documents;

use App\Helpers\Invoice\Util;
use App\Models\Company as MyCompany;
use App\Models\SaleDocument;
use DateTime;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Voided\Voided;
use Greenter\Model\Voided\VoidedDetail;
use Illuminate\Support\Facades\Schema;
use Modules\Sales\Entities\SaleLowcoDetail;
use Modules\Sales\Entities\SaleLowCommunication;
use Throwable;

/**
 * Comunicación de baja (Voided) contra SUNAT.
 *
 * Además de enviar y consultar el ticket, este servicio:
 *  - guarda el XML firmado y el ZIP del CDR devuelto por SUNAT;
 *  - traduce los errores de Greenter/SUNAT a una alerta clara para el usuario
 *    (ver LowCommunicationAlerts);
 *  - solo marca las facturas como anuladas cuando SUNAT las acepta (CDR código 0).
 */
class LowCommunication
{
    /** Tipos de documento que SUNAT admite en una comunicación de baja. */
    private const TIPOS_BAJA = ['01', '07', '08'];

    protected $util;

    protected $mycompany;

    /** Cache de la comprobación de columnas nuevas (para despliegues sin migrar). */
    private static ?bool $supportsAlertColumns = null;

    public function __construct()
    {
        $this->util = Util::getInstance();
        $this->mycompany = MyCompany::first();
    }

    /**
     * Genera la comunicación de baja y la envía a SUNAT.
     */
    public function create($voided, $documents): array
    {
        try {
            if (count($this->selectedDocuments($documents)) === 0) {
                return $this->localFailure('No se seleccionó ningún documento: marca al menos una factura e indica el motivo de la baja.');
            }

            $sum = $this->createVoided($voided, $documents);
            // Envio a SUNAT.
            $see = $this->util->getSee();
            $res = $see->send($sum);

            $voided->xml = $this->util->writeXml($sum, $see->getFactory()->getLastXml());
            $voided->communication_name = $sum->getName();

            if ($res->isSuccess()) {
                $ticket = $res->getTicket();
                $voided->ticket = $ticket;

                // Algunos servicios devuelven el CDR en el propio envío.
                $cdrZip = $this->responseCdrZip($res);
                if ($cdrZip !== null) {
                    $voided->cdr = $this->util->writeCdr($sum, $cdrZip);
                }

                $alert = $cdrZip !== null
                    ? LowCommunicationAlerts::accepted(null, null, [], true)
                    : LowCommunicationAlerts::sentToSunat($ticket);
            } else {
                $error = $res->getError();
                $alert = LowCommunicationAlerts::failure(
                    $error ? $error->getCode() : null,
                    $error ? $error->getMessage() : null,
                    [],
                    'send'
                );
            }

            $this->persist($voided, $alert, null, 'send');

            return $this->payload($alert, $voided);
        } catch (Throwable $e) {
            return $this->localFailure($e->getMessage(), $voided);
        }
    }

    /**
     * Consulta el ticket de la baja y procesa el CDR devuelto por SUNAT.
     */
    public function checkLowCommunication($id, $ticket): array
    {
        $voided = SaleLowCommunication::find($id);

        if (! $voided) {
            return $this->localFailure('No se encontró la comunicación de baja solicitada.');
        }

        try {
            $documents = SaleLowcoDetail::where('lowco_id', $id)
                ->get()
                ->toArray();

            if (count($documents) === 0) {
                return $this->localFailure('La comunicación de baja no tiene documentos registrados; vuelve a generarla desde el listado.', $voided);
            }

            $see = $this->util->getSee();
            $sum = $this->createVoided($voided, $documents, true);

            $res = $see->getStatus($ticket);

            $notes = null;

            if (! $res->isSuccess()) {
                $error = $res->getError();
                $alert = LowCommunicationAlerts::failure(
                    $error ? $error->getCode() : null,
                    $error ? $error->getMessage() : null,
                    [],
                    'check'
                );
            } else {
                $cdr = $res->getCdrResponse();

                // El ZIP del CDR es la prueba de lo que respondió SUNAT: se guarda siempre.
                $cdrZip = $this->responseCdrZip($res);
                if ($cdrZip !== null) {
                    $voided->cdr = $this->util->writeCdr($sum, $cdrZip);
                }

                $cdrNotes = $cdr && $cdr->getNotes() ? $cdr->getNotes() : [];
                if (count($cdrNotes) > 0) {
                    $notes = json_encode($cdrNotes, JSON_UNESCAPED_UNICODE);
                }

                $cdrCode = $cdr ? (string) $cdr->getCode() : null;
                $statusCode = method_exists($res, 'getCode') ? (string) $res->getCode() : null;

                if ($cdr === null) {
                    // Sin CDR: SUNAT aún no terminó (98) o respondió algo inesperado.
                    $alert = ($statusCode === '98' || $statusCode === '0098')
                        ? LowCommunicationAlerts::processing($statusCode, 'SUNAT todavía no devuelve el CDR de la baja.')
                        : LowCommunicationAlerts::failure($statusCode, 'SUNAT no devolvió el CDR de la comunicación de baja.', [], 'check');
                } elseif ($cdrCode === '0') {
                    $alert = LowCommunicationAlerts::accepted($cdrCode, $cdr->getDescription(), $cdrNotes, $cdrZip !== null);

                    // Solo se anulan los comprobantes cuando SUNAT aceptó la baja.
                    $this->markDocumentsAsAnnulled($documents, $voided, $ticket);
                } else {
                    // CDR con código distinto de 0: baja rechazada, las facturas siguen vigentes.
                    $alert = LowCommunicationAlerts::failure($cdrCode, $cdr->getDescription(), $cdrNotes, 'check');
                }
            }

            $this->persist($voided, $alert, $notes, 'check');

            return $this->payload($alert, $voided);
        } catch (Throwable $e) {
            return $this->localFailure($e->getMessage(), $voided);
        }
    }

    public function createVoided($low, $documents, $ticket = false)
    {
        $items = [];

        $company = new Company;

        $province = $this->mycompany->district->province;
        $department = $province->department;

        $company->setRuc($this->mycompany->ruc)
            ->setRazonSocial($this->mycompany->business_name)
            ->setNombreComercial($this->mycompany->tradename)
            ->setAddress((new Address)
                ->setUbigueo($this->mycompany->ubigueo)
                ->setDepartamento($department->name)
                ->setProvincia($province->name)
                ->setDistrito($this->mycompany->district->name)
                ->setUrbanizacion('-')
                ->setDireccion($this->mycompany->fiscal_address));

        foreach ($documents as $document) {
            if ($ticket === false && empty($document['edit_low'])) {
                continue;
            }

            $tipoDoc = (string) ($document['invoice_type_doc'] ?? '');

            if (! in_array($tipoDoc, self::TIPOS_BAJA, true)) {
                throw new \InvalidArgumentException(
                    'El tipo de documento '.$tipoDoc.' no admite comunicación de baja. Solo se pueden dar de baja facturas (01) y notas de crédito/débito (07/08).'
                );
            }

            $motivo = trim((string) ($ticket === false
                ? ($document['description_low'] ?? '')
                : ($document['invoice_description'] ?? '')));

            if ($motivo === '') {
                throw new \InvalidArgumentException('Indica el motivo de la baja de la serie-correlativo '.($document['invoice_serie'] ?? '').'-'.($document['number'] ?? $document['invoice_correlative'] ?? '').'.');
            }

            $detail = new VoidedDetail;
            $detail->setTipoDoc($tipoDoc)
                ->setSerie($document['invoice_serie'])
                ->setCorrelativo($document['invoice_correlative'])
                ->setDesMotivoBaja($motivo);

            array_push($items, $detail);
        }

        if (count($items) === 0) {
            throw new \InvalidArgumentException('No hay documentos válidos para incluir en la comunicación de baja.');
        }

        // Fecha Generacion menor que Fecha Resumen
        $generation_date = new DateTime($low->generation_date);
        $communication_date = new DateTime($low->communication_date);

        $voided = new Voided;
        $voided->setCorrelativo($low->correlative)
            // Fecha Generacion menor que Fecha comunicacion
            ->setFecGeneracion($generation_date)
            ->setFecComunicacion($communication_date)
            ->setCompany($company)
            ->setDetails($items);

        return $voided;
    }

    /**
     * Documentos marcados por el usuario para dar de baja.
     */
    protected function selectedDocuments($documents): array
    {
        return collect($documents)
            ->filter(fn ($document) => ! empty($document['edit_low']))
            ->values()
            ->all();
    }

    /**
     * Marca como anulados los comprobantes de una baja aceptada por SUNAT.
     */
    protected function markDocumentsAsAnnulled(array $documents, $voided, ?string $ticket): void
    {
        $factura = new Factura;

        foreach ($documents as $document) {
            $documentId = $document['document_id'] ?? null;

            if (! $documentId) {
                continue;
            }

            SaleDocument::where('id', $documentId)
                ->update([
                    'invoice_status' => 'Anulada',
                    'invoice_response_code' => 0,
                    'invoice_response_description' => 'Comunicacion de baja '.$voided->communication_name.' Número ticket: '.$ticket,
                    'reason_cancellation' => $document['invoice_description'] ?? null,
                ]);

            $factura->updateStockSale($documentId);
        }
    }

    /**
     * Guarda el resultado (estado + alerta traducida) en la comunicación.
     */
    protected function persist($voided, array $alert, ?string $notes = null, string $stage = 'send'): void
    {
        $voided->response_code = $alert['code'] !== null && $alert['code'] !== ''
            ? mb_substr((string) $alert['code'], 0, 10)
            : null;

        $voided->response_description = trim(
            $alert['message'].($alert['detail'] ? ' | Detalle técnico: '.$alert['detail'] : '')
        );

        $voided->notes = $notes;

        // Un error interno no debe borrar el estado que SUNAT ya había dado
        // (por ejemplo, una baja 'Enviado' que falla al reconectar).
        if ($alert['result'] !== LowCommunicationAlerts::RESULT_ERROR_LOCAL || ! $voided->status) {
            $voided->status = $alert['status'];
        }

        if ($this->supportsAlertColumns()) {
            $voided->forceFill([
                'sunat_result' => $alert['result'],
                'error_kind' => $alert['kind'],
                'alert_level' => $alert['level'],
                'alert_title' => mb_substr((string) $alert['title'], 0, 150),
                'alert_message' => $alert['message'],
                'recommended_action' => $alert['action'],
                'cdr_code' => $alert['cdr_code'],
                'cdr_description' => $alert['cdr_description'],
                'last_checked_at' => now(),
            ]);

            $voided->check_attempts = (int) ($voided->check_attempts ?? 0) + ($stage === 'check' ? 1 : 0);
        }

        $voided->save();
    }

    protected function payload(array $alert, $voided = null): array
    {
        $result = $alert['result'];
        $sent = in_array($result, [
            LowCommunicationAlerts::RESULT_ACEPTADO,
            LowCommunicationAlerts::RESULT_EN_PROCESO,
            LowCommunicationAlerts::RESULT_YA_PRESENTADO,
        ], true);

        return [
            // 'success' = SUNAT recibió/aceptó la baja (usado para actualizar documentos).
            'success' => in_array($result, [
                LowCommunicationAlerts::RESULT_ACEPTADO,
                LowCommunicationAlerts::RESULT_EN_PROCESO,
            ], true),
            'sent' => $sent,
            'code' => $alert['code'],
            'message' => $alert['message'],
            'notes' => $alert['notes'] ? json_encode($alert['notes'], JSON_UNESCAPED_UNICODE) : null,
            'alert' => $alert,
            'status' => $alert['status'],
            'ticket' => $voided ? $voided->ticket : null,
            'has_cdr' => (bool) ($voided ? $voided->cdr : false),
            'lowco_id' => $voided ? $voided->id : null,
            'is_processing' => $result === LowCommunicationAlerts::RESULT_EN_PROCESO,
        ];
    }

    protected function localFailure(string $message, $voided = null): array
    {
        $alert = LowCommunicationAlerts::localError($message);

        if ($voided instanceof \Illuminate\Database\Eloquent\Model && $voided->exists) {
            $this->persist($voided, $alert, null, 'local');
        }

        return $this->payload($alert, $voided);
    }

    protected function responseCdrZip($response): ?string
    {
        if (! is_object($response) || ! method_exists($response, 'getCdrZip')) {
            return null;
        }

        $zip = $response->getCdrZip();

        return is_string($zip) && $zip !== '' ? $zip : null;
    }

    /**
     * Permite desplegar el código antes de ejecutar la migración: si las
     * columnas nuevas todavía no existen, se sigue guardando lo histórico.
     */
    protected function supportsAlertColumns(): bool
    {
        if (self::$supportsAlertColumns === null) {
            try {
                self::$supportsAlertColumns = Schema::hasColumn('sale_low_communications', 'sunat_result');
            } catch (Throwable $e) {
                self::$supportsAlertColumns = false;
            }
        }

        return self::$supportsAlertColumns;
    }
}
