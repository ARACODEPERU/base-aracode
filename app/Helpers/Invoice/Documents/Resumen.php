<?php

namespace App\Helpers\Invoice\Documents;

use App\Helpers\Invoice\Util;
use App\Models\Company as MyCompany;
use App\Models\Parameter;
use App\Models\SaleDocument;
use DateTime;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Summary\Summary;
use Greenter\Model\Summary\SummaryDetail;
use Modules\Sales\Entities\SaleSummary;

class Resumen
{
    protected $see;

    protected $util;

    protected $mycompany;

    private $igv;

    public function __construct()
    {
        $this->util = Util::getInstance();
        $this->mycompany = MyCompany::first();
        $this->igv = Parameter::where('parameter_code', 'P000001')->value('value_default');
    }

    /**
     * Detecta errores a nivel de transporte HTTP (Greenter los reporta con
     * código 'HTTP' o mensajes como 'Bad Request', 'connection', 'timeout', 'soap').
     *
     * En estos casos SUNAT NO procesó el archivo: no es un rechazo de negocio,
     * por lo que el resumen debe permanecer reintentable/reconsultable.
     */
    private function isHttpTransportError(?string $code, ?string $message): bool
    {
        $message = (string) $message;

        return $code === 'HTTP'
            || stripos($message, 'HTTP') !== false
            || stripos($message, 'bad request') !== false
            || stripos($message, 'connection') !== false
            || stripos($message, 'timeout') !== false
            || stripos($message, 'soap') !== false;
    }

    /**
     * Cuando SUNAT responde 2223 (el archivo ya fue presentado) intenta recuperar
     * la constancia (CDR) consultando el ticket: el devuelto por el envío, el que
     * venga dentro del mensaje de SUNAT o el ya guardado en el resumen.
     *
     * Devuelve null si no hay ticket con el que consultar. En otro caso devuelve
     * ['status' => ..., 'code' => ..., 'message' => ..., 'notes' => ..., 'ticket' => ..., 'recovered' => bool]
     */
    private function recoverCdrFromTicket($see, $sum, $summary, $response, $codeError, $messageError)
    {
        $ticket = $response ? $response->getTicket() : null;

        if (! $ticket) {
            preg_match('/ticket[:\s]+([A-Z0-9\-]+)/i', (string) $messageError, $matches);
            $ticket = $matches[1] ?? null;
        }

        if (! $ticket) {
            $ticket = $summary->ticket;
        }

        if (! $ticket) {
            return null;
        }

        $summary->ticket = $ticket;

        try {
            $statusResult = $see->getStatus($ticket);
        } catch (\Exception $e) {
            return [
                'status' => 'Enviado',
                'code' => $codeError,
                'message' => 'El archivo ya fue presentado a SUNAT pero no se pudo recuperar la constancia: '.$e->getMessage(),
                'notes' => null,
                'ticket' => $ticket,
                'recovered' => false,
            ];
        }

        if (! $statusResult->isSuccess()) {
            return [
                'status' => 'Enviado',
                'code' => $codeError,
                'message' => 'El archivo ya fue recibido por SUNAT pero aún no se pudo obtener la constancia. Ticket: '.$ticket.'. Puede volver a Consultar.',
                'notes' => null,
                'ticket' => $ticket,
                'recovered' => false,
            ];
        }

        $cdr = $statusResult->getCdrResponse();
        $notes = $cdr->getNotes() ? json_encode($cdr->getNotes(), JSON_UNESCAPED_UNICODE) : null;

        $summary->cdr = $this->util->writeCdr($sum, $statusResult->getCdrZip());

        return [
            'status' => $cdr->getCode() == 0 ? 'Aceptado' : 'Rechazado',
            'code' => $cdr->getCode(),
            'message' => $cdr->getDescription(),
            'notes' => $notes,
            'ticket' => $ticket,
            'recovered' => true,
        ];
    }

    public function create($summary, $documents)
    {
        try {
            $sum = $this->createSum($summary, $documents);
            // Envio a SUNAT.
            $see = $this->util->getSee();
            $res = $see->send($sum);

            $summary->xml = $this->util->writeXml($sum, $see->getFactory()->getLastXml());
            $summary->summary_name = $sum->getName();

            $notes = null;
            $status = null;
            $codeError = null;
            $messageError = null;
            $cdrRecovered = false;

            if ($res->isSuccess()) {

                $ticket = $res->getTicket();
                $status = 'Enviado';
                $summary->ticket = $ticket;
            } else {

                $error = $res->getError();
                $codeError = $error->getCode();
                $messageError = $error->getMessage();

                if ($codeError === '0109' || stripos($messageError, '0109') !== false) {
                    $status = 'sunat_disponible';
                } elseif ($codeError === '2223' || stripos($messageError, '2223') !== false) {
                    // El archivo ya fue presentado (mismo resumen). No es un rechazo:
                    // se intenta recuperar la constancia (CDR) con el ticket disponible.
                    $recovery = $this->recoverCdrFromTicket($see, $sum, $summary, $res, $codeError, $messageError);

                    if ($recovery && $recovery['recovered']) {
                        $status = $recovery['status'];
                        $codeError = $recovery['code'];
                        $messageError = $recovery['message'];
                        $notes = $recovery['notes'];
                        $cdrRecovered = true;
                    } else {
                        // Sin constancia recuperable: queda como "ya enviado", pero
                        // guardando el ticket (si el mensaje lo trae) para poder consultar.
                        $status = 'fue_enviado';
                        if ($recovery) {
                            $messageError = $recovery['message'];
                        }
                    }
                } elseif ($this->isHttpTransportError($codeError, $messageError)) {
                    // Error HTTP (ej. "Bad Request"): SUNAT no procesó el archivo.
                    // No es un rechazo: queda disponible para reenviar.
                    $status = 'sunat_disponible';
                } else {
                    $status = 'Rechazado';
                }
            }
            $summary->response_code = $codeError;
            $summary->response_description = $messageError;
            $summary->notes = $notes;
            $summary->status = $status;

            $summary->save();

            $isSunatUnavailable = $status === 'sunat_disponible';
            $isAlreadySent = $status === 'fue_enviado';

            return [
                'success' => $res->isSuccess() || $cdrRecovered,
                'code' => $codeError,
                'message' => $messageError,
                'notes' => $notes,
                'is_sunat_unavailable' => $isSunatUnavailable,
                'is_already_sent' => $isAlreadySent,
                'cdr_recovered' => $cdrRecovered,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'code' => 0, 'message' => $e->getMessage(), 'notes' => 'Error de falta de datos en el sistema'];
        }
    }

    public function checkSummary($id, $ticket)
    {

        try {
            $summary = SaleSummary::find($id);
            $documents = SaleDocument::join('sale_summary_details', 'sale_summary_details.document_id', 'sale_documents.id')
                ->select('sale_documents.*')
                ->where('summary_id', $id)
                ->get()
                ->toArray();

            $see = $this->util->getSee();
            $sum = $this->createSum($summary, $documents);

            $notes = null;
            $status = null;
            $codeError = null;
            $messageError = null;

            $res = $see->getStatus($ticket);

            if (! $res->isSuccess()) {
                $error = $res->getError();
                $codeError = $error->getCode();
                $messageError = $error->getMessage();

                // SUNAT aún está procesando el comprobante (envío asíncrono).
                // No es un error: se mantiene 'Enviado' para poder volver a consultar después.
                $isProcessing =
                    $codeError === '0098'
                    || $codeError === '98'
                    || stripos((string) $messageError, 'no ha terminado') !== false
                    || stripos((string) $messageError, 'procesamiento') !== false;

                // Error 0109 - SUNAT autenticación no disponible
                if ($codeError === '0109' || stripos($messageError, '0109') !== false) {
                    $isSunatUnavailable = true;
                    $status = 'sunat_disponible';
                }
                // Error 2223 - Archivo ya presentado previamente: intentar recuperar el CDR
                elseif ($codeError === '2223' || stripos($messageError, '2223') !== false) {
                    $recovery = $this->recoverCdrFromTicket($see, $sum, $summary, $res, $codeError, $messageError);

                    if ($recovery && $recovery['recovered']) {
                        $codeError = $recovery['code'];
                        $messageError = $recovery['message'];
                        $notes = $recovery['notes'];
                        $status = $recovery['status'];
                        $isAlreadySent = false;

                        if ($status === 'Aceptado') {
                            foreach ($documents as $document) {
                                SaleDocument::where('id', $document['id'])
                                    ->update([
                                        'invoice_status' => 'Aceptada',
                                        'invoice_response_code' => 0,
                                        'invoice_response_description' => 'Enviado en resumen '.$summary->summary_name.' Número ticket: '.$recovery['ticket'],
                                    ]);
                            }
                        }
                    } else {
                        $isAlreadySent = true;
                        $status = 'fue_enviado';
                        if ($recovery) {
                            $messageError = $recovery['message'];
                        }
                    }
                }
                // Otros errores - detectar conexión, "en proceso" o rechazo real
                else {
                    $isConnectionError =
                        empty($codeError)
                        || $this->isHttpTransportError($codeError, $messageError)
                        || stripos($messageError, 'servidor') !== false;

                    if ($isConnectionError || $isProcessing) {
                        // SUNAT no respondió bien: NO se toca el estado.
                        // El resumen mantiene su estado actual (ej. 'Enviado') y
                        // el usuario puede volver a Consultar con el mismo ticket.
                        $status = $summary->status;
                    } else {
                        $status = 'Rechazado';
                    }
                }
            } else {
                $cdr = $res->getCdrResponse();
                $codeError = $cdr->getCode();
                $messageError = $cdr->getDescription();
                if ($cdr->getNotes()) {
                    $notes = json_encode($cdr->getNotes(), JSON_UNESCAPED_UNICODE);
                }
                foreach ($documents as $document) {
                    SaleDocument::where('id', $document['id'])
                        ->update([
                            'invoice_status' => 'Aceptada',
                            'invoice_response_code' => 0,
                            'invoice_response_description' => 'Enviado en resumen '.$summary->summary_name.' Número ticket: '.$ticket,
                        ]);
                }
                $status = $cdr->getCode() == 0 ? 'Aceptado' : null;
                $summary->cdr = $this->util->writeCdr($sum, $res->getCdrZip());
            }

            $summary->response_code = $codeError;

            // Mensaje personalizado según tipo de error
            if (isset($isSunatUnavailable) && $isSunatUnavailable) {
                $summary->response_description = 'Los servidores de SUNAT no están disponibles temporalmente. Puede reintentar el envío manualmente. Detalle: '.$messageError;
            } elseif (isset($isAlreadySent) && $isAlreadySent) {
                $summary->response_description = 'El archivo ya fue presentado anteriormente ante SUNAT. Detalle: '.$messageError;
            } elseif (isset($isProcessing) && $isProcessing) {
                $summary->response_description = 'SUNAT sigue procesando el comprobante. No es un error del sistema; vuelve a consultar más tarde. Detalle: '.$messageError;
            } elseif (isset($isConnectionError) && $isConnectionError) {
                $summary->response_description = 'Error de conexión con SUNAT a nivel HTTP. El comprobante NO fue procesado; puedes volver a consultar más tarde. Detalle: '.$messageError;
            } else {
                $summary->response_description = $codeError == '0127' ? 'El ticket no existe' : $messageError;
            }

            $summary->notes = $notes;
            $summary->status = $status;
            $summary->save();

            return [
                'success' => $res->isSuccess(),
                'code' => $codeError,
                'message' => $messageError,
                'notes' => $notes,
                'is_connection_error' => isset($isConnectionError) ? $isConnectionError : false,
                'is_sunat_unavailable' => isset($isSunatUnavailable) ? $isSunatUnavailable : false,
                'is_already_sent' => isset($isAlreadySent) ? $isAlreadySent : false,
                'is_processing' => isset($isProcessing) ? (bool) $isProcessing : false,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'code' => 0, 'message' => $e->getMessage(), 'notes' => 'Error de falta de datos en el sistema'];
        }
    }

    public function createSum($summary, $documents)
    {
        $items = [];

        // Los llamadores entregan los documentos como array (p. ej. ->toArray()
        // o el payload del request). Se normaliza a colección para poder usar
        // $documents->first() sin importar el origen.
        if (! $documents instanceof \Illuminate\Support\Collection) {
            $documents = collect($documents);
        }

        $company = new Company;

        $province = $this->mycompany->district->province;
        $department = $province->department;

        $company->setRuc($this->mycompany->ruc)
            ->setRazonSocial($this->mycompany->business_name)
            ->setNombreComercial($this->mycompany->tradename)
            ->setAddress((new Address)
                ->setUbigueo($this->mycompany->ubigeo)
                ->setDepartamento($department->name)
                ->setProvincia($province->name)
                ->setDistrito($this->mycompany->district->name)
                ->setUrbanizacion('-')
                ->setDireccion($this->mycompany->fiscal_address));

        foreach ($documents as $document) {
            $detiail = new SummaryDetail;
            if ($document['invoice_type_doc'] == '03') {
                $serie_number = $document['invoice_serie'].'-'.$document['invoice_correlative'];
                // /estados de boleta
                // /1= Se esta informando por primera vez.
                // /2= Se informó previamente y se quiere editar sus valores.
                // /3= Se quiere anular el comprobante
                $detiail->setTipoDoc($document['invoice_type_doc'])
                    ->setSerieNro($serie_number)
                    ->setEstado($document['status'])
                    ->setClienteTipo($document['client_type_doc'])
                    ->setClienteNro($document['client_number'])
                    ->setTotal($document['invoice_mto_imp_sale'])
                    ->setMtoOperGravadas($document['invoice_mto_oper_taxed'])
                    ->setMtoOperInafectas($document['invoice_mto_oper_unaffected'])
                    ->setMtoOperExoneradas($document['invoice_mto_oper_exonerated'])
                    ->setMtoOperExportacion($document['invoice_mto_oper_export'])
                    ->setMtoOtrosCargos($document['invoice_mto_oper_other_charges'])
                    ->setMtoIGV($document['invoice_total_taxes'])
                    ->setPorcentajeIgv((int) $this->igv);
            }

            array_push($items, $detiail);
        }

        $sum = new Summary;
        // Moneda del resumen: SUNAT agrupa por moneda; se toma la del documento
        // (los resumenes se generan por documento, sea PEN o USD segun corresponda)
        $first = $documents->first();
        $summaryCurrency = is_array($first) ? ($first['invoice_type_currency'] ?? 'PEN') : ($first?->invoice_type_currency ?? 'PEN');
        // Fecha Generacion menor que Fecha Resumen
        $generation_date = new DateTime($summary->generation_date);
        $summary_date = new DateTime($summary->summary_date);
        $sum->setFecGeneracion($generation_date)
            ->setFecResumen($summary_date)
            ->setCorrelativo($summary->correlative)
            ->setMoneda($summaryCurrency)
            ->setCompany($company)
            ->setDetails($items);

        return $sum;
    }
}
