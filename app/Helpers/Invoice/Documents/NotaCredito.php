<?php

namespace App\Helpers\Invoice\Documents;

use App\Helpers\Invoice\Models\Note;
use Carbon\Carbon;
use DateTime;
use App\Models\Company as MyCompany;
use App\Models\User;
use App\Helpers\Invoice\Util;
use App\Models\LocalSale;
use App\Models\SaleDocument;
use App\Models\SaleDocumentItem;
use Exception;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Client\Client;
use Greenter\Model\Sale\Charge;
use Greenter\Model\Sale\Legend;
use Greenter\Model\Sale\SaleDetail;
use App\Helpers\Invoice\QrCodeGenerator;
use App\Models\District;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class NotaCredito
{
    protected $see;
    protected $util;
    protected $mycompany;


    public function __construct()
    {
        $this->util = Util::getInstance();
        $this->mycompany = MyCompany::first();
    }

    public function create($document)
    {
        try {
            $invoice = SaleDocument::where('id', $document->document_id)->with('sale')->first();
            $note = $this->setDocument($document, $invoice);

            $see = $this->util->getSee();

            $res = $see->send($note);

            //fecha en la que se envio a sunat el documento
            $document->invoice_send_date = Carbon::now();

            $document->invoice_xml = $this->util->writeXml($note, $see->getFactory()->getLastXml());
            $document->invoice_document_name = $note->getName();
            $notes = null;
            $status = null;

            if ($res->isSuccess()) {
                /**@var $res \Greenter\Model\Response\BillResult*/
                $cdr = $res->getCdrResponse();
                $codeError = $cdr->getCode();
                $messageError = $cdr->getDescription();
                $notes = json_encode($cdr->getNotes(), JSON_UNESCAPED_UNICODE);
                if ($cdr->getCode() == 0) {
                    $status = 'Aceptada';
                    $invoice->status = 3;
                    $invoice->invoice_status = 'Por anular';
                    $invoice->save();

                    Sale::find($invoice->sale_id)->update([
                        'status' => 0
                    ]);

                } elseif ($cdr->getCode() == 2325) {
                    $status = 'Pendiente';
                }
                $document->invoice_cdr = $this->util->writeCdr($note, $res->getCdrZip());
            } else {
                $error = $res->getError();
                $codeError = $error->getCode();
                $messageError = $error->getMessage();
                $status = 'Rechazada';
                //return array('success' => $res->isSuccess(), 'details' => $this->util->getErrorResponse($res->getError()));

            }

            $document->invoice_response_code = $codeError;
            $document->invoice_response_description = $messageError;
            $document->invoice_notes = $notes;
            $document->invoice_status = $status;

            $document->save();

            return array('success' => $res->isSuccess(), 'code' => $codeError, 'message' => $messageError, 'notes' => $notes);
        } catch (\Exception $e) {
            return array('success' => false, 'code' => 0, 'message' => $e->getMessage(), 'notes' => 'Error de falta de datos en el sistema');
        }
    }

    public function setDocument($document, $invoice)
    {
        $note = new Note();
        $establishment = LocalSale::find($invoice->sale->local_id);
        $province = $establishment->district->province;

        $setDesMotivo = DB::table('sunat_note_credit_types')->where('id',$document->note_type_operation_id)->value('description');

        $department = $province->department;


        $clientCity = District::with('province.department')->where('id',$document->client_ubigeo_code)->first();

        $client = (new Client())
            ->setTipoDoc($document->client_type_doc)
            ->setNumDoc($document->client_number)
            ->setRznSocial($document->client_rzn_social);

        if($clientCity ){
            $clientAddress = (new Address())
                ->setUbigueo($document->client_ubigeo_code)
                ->setDepartamento($clientCity->province->department->name)
                ->setProvincia($clientCity->province->name)
                ->setDistrito($clientCity->name)
                ->setUrbanizacion('-')
                ->setDireccion($document->client_address);

            $client->setAddress($clientAddress);
        }

        // Emisor
        $address = (new Address())
            ->setUbigueo($establishment->ubigeo)
            ->setDepartamento($department->name)
            ->setProvincia($province->name)
            ->setDistrito($establishment->district->name)
            ->setUrbanizacion('-')
            ->setDireccion($establishment->address)
            ->setCodLocal($establishment->sunat_code); // Codigo de establecimiento asignado por SUNAT, 0000 por defecto.


        $company = (new Company())
            ->setRuc($this->mycompany->ruc)
            ->setRazonSocial($this->mycompany->business_name)
            ->setNombreComercial($this->mycompany->tradename)
            ->setEmail($this->mycompany->email)
            ->setTelephone($this->mycompany->phone)
            ->setAddress($address);

        $broadcast_date = new DateTime($document->invoice_broadcast_date . ' ' . Carbon::parse($document->created_at)->format('H:m:s'));
        $invoice_name = $invoice->invoice_serie . '-' . $invoice->invoice_correlative;

        $afe_broadcast_date = new DateTime($invoice->invoice_broadcast_date . ' ' . Carbon::parse($invoice->created_at)->format('H:m:s'));

        ////2.0 la version para notas
        $note->setUblVersion($document->invoice_ubl_version)
            ->setTipoDoc($document->invoice_type_doc)
            ->setSerie($document->invoice_serie)
            ->setCorrelativo($document->invoice_correlative)
            ->setFechaEmision($broadcast_date)
            ->setTipDocAfectado($invoice->invoice_type_doc) // Tipo Doc: Factura
            ->setNumDocfectado($invoice_name) // Factura: Serie-Correlativo
            ->setCodMotivo($document->note_type_operation_id) // Catalogo. 09
            ->setDesMotivo($document->reason_cancellation)
            ->setTipoMoneda($invoice->invoice_type_currency)
            ->setIdDocAfectado($invoice->id)
            ->setFechaDocAfectado($afe_broadcast_date)
            ->setDesTypeMotivo($setDesMotivo)
            // ->setGuias([/* Guias (Opcional) */
            //     (new Document())
            //         ->setTipoDoc('09')
            //         ->setNroDoc('0001-213')
            // ])
            ->setCompany($company)
            ->setClient($client)
            ->setMtoOperGravadas($invoice->invoice_mto_oper_taxed)
            ->setMtoIGV($invoice->invoice_mto_igv)
            ->setTotalImpuestos($invoice->invoice_total_taxes)
            ->setMtoImpVenta($invoice->invoice_mto_imp_sale);


        $details = SaleDocumentItem::where('document_id', $document->id)->get();

        $items = [];

        foreach ($details as $detail) {

            $item = new SaleDetail();
            $item->setCodProducto($detail->product_id)
                ->setUnidad($detail->unit_type)
                ->setCantidad($detail->quantity)
                ->setDescripcion($detail->decription_product)
                ->setMtoBaseIgv($detail->mto_base_igv)
                ->setPorcentajeIgv($detail->percentage_igv)
                ->setIgv($detail->igv)
                ->setTipAfeIgv($detail->type_afe_igv)
                ->setTotalImpuestos($detail->total_tax)
                ->setMtoValorVenta($detail->mto_value_sale)
                ->setMtoValorUnitario($detail->mto_value_unit)
                ->setMtoPrecioUnitario($detail->mto_price_unit);

            $descuent = $detail->mto_discount;

            if ($descuent > 0) {

                $item->setDescuento($descuent);

                $json_discounts = json_decode($detail->json_discounts);

                $charges = [];

                foreach ($json_discounts as $k => $json_discount) {
                    $charges[$k] = (new Charge())
                        ->setCodTipo($json_discount->type)
                        ->setMontoBase($json_discount->base)
                        ->setFactor($json_discount->factor)
                        ->setMonto($json_discount->monto);
                }

                $item->setDescuentos($charges);
            }

            array_push($items, $item);
        }

        $legend = new Legend();
        $legend->setCode($document->invoice_legend_code)
            ->setValue($document->invoice_legend_description);

        $note->setDetails($items)
            ->setLegends([$legend]);
        //dd($note);

        return $note;
    }

    public function getNotaCreditoPdf($id, $format = 'A4')
    {
        try {
            $document = SaleDocument::find($id);
            $afectada = SaleDocument::where('id', $document->document_id)->with('sale')->first();
            $invoice = $this->setDocument($document, $afectada);
            // dd($invoice);
            $generator = new QrCodeGenerator(300);
            $dir = public_path() . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp_qr';
            $cadenaqr = $this->stringQr($document);

            $qr_path = $generator->generateQR($cadenaqr, $dir, $invoice->getName() . '.png', 8, 2);


            $seller = User::find($document->user_id);

            $pdf = $this->util->generatePdf($invoice, $seller, $qr_path, $format, $document->status);

            $document->invoice_pdf = $pdf;
            $document->save();

            return array(
                'fileName' => $invoice->getName() . '.pdf',
                'filePath' => $document->invoice_pdf
            );
        } catch (Exception $e) {
            var_dump($e);
        }
    }
    public function getNotaCreditoXML($id)
    {
        try {
            $document = SaleDocument::find($id);

            return array(
                'fileName' => $document->invoice_document_name . '.xml',
                'filePath' => $document->invoice_xml
            );
        } catch (Exception $e) {
            var_dump($e);
        }
    }
    public function getNotaCreditoCDR($id)
    {
        try {
            $document = SaleDocument::find($id);

            return array(
                'fileName' => $document->invoice_document_name . '.zip',
                'filePath' => $document->invoice_cdr
            );
        } catch (Exception $e) {
            var_dump($e);
        }
    }
    public function stringQr($document)
    {
        return $this->mycompany->ruc . '|' . $document->invoice_type_doc . '|' . $document->invoice_serie . '|' . $document->invoice_correlative . '|' . $document->invoice_mto_imp_sale . '|' . $document->invoice_broadcast_date . '|' . $document->client_type_doc . '|' . $document->client_number;
    }
}
